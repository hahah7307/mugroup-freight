<?php
namespace app\Manage\controller;

use app\Manage\model\OrderAddressModel;
use app\Manage\model\OrderDetailModel;
use app\Manage\model\OrderModel;
use app\Manage\model\ProductModel;
use SoapClient;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\Session;
use think\Config;

class OrderController extends BaseController
{
    /**
     * @throws DbException
     */
    public function index(): \think\response\View
    {
        $keyword = $this->request->get('keyword', '', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        if ($keyword) {
            $where['refNo|saleOrderCode|sysOrderCode'] = ['like', '%' . $keyword . '%'];
        } else {
            $where = [];
        }

        $page_num = $this->request->get('page_num', Config::get('PAGE_NUM'));
        $this->assign('page_num', $page_num);

        // 订单列表
        $order = new OrderModel();
        $list = $order->with(['details.product','address'])->where($where)->order('id asc')->paginate($page_num);
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws Exception
     */
    public function orderSearch()
    {
        header("content-type:text/html;charset=utf-8");
        Db::startTrans();
        try {
//            $code = ',"saleOrderCodes":["WAL-0015-108830554985272","WAL-0015-108830555497775","WAL-0015-108830556126577","WAL-0015-108830556150466","WAL-0015-108830556197902","WAL-0015-108830556235667","WAL-0015-108830556447654","111-4993938-8145837","114-6650471-9181851","WAL-0015-108830656606570","WEC0202311010027","WEC0732311010026","112-1331038-7953855","111-8253014-9120241","113-6172109-7762643","112-8199898-0941002","112-8199898-0941002","113-0107617-8006662","WAL-0015-108830656762419","112-3752166-1517843","111-9353603-9485812","111-6369802-4590660","111-2690812-0133010","112-1828116-3325002-1","113-4924029-6045839","112-3417797-4629833","113-9391384-5302622","111-0457987-6445826","112-6713942-5516220","114-8264835-6565064","113-2705013-6769026","111-4856047-1358654","113-1670085-6347456","113-1302402-7188241","111-8797285-4989815","112-6949115-2413034","113-4347451-3694649","111-5764226-6632248","111-1107882-2973852","112-0880236-6521032","111-4630657-7587411","114-8692453-7600266","111-3743330-5502662","WAL-0015-108830143238986","WAL-0015-108830143479768","112-4020661-0603410","112-5144909-5204201","114-7744917-2294662","114-2361526-0073045","WEC0822310280049","WEC0962310280048","WEC0492310280046","WEC0342310280045","WEC0862310280047","114-6552881-7855421","114-4108375-1805014","112-5361955-7671445","111-1260460-3122628","113-0362936-2428262","113-5090614-4454653","113-2530577-9504239"]';
            $code = '';
            $url = "http://nt5e7hf-eb.eccang.com/default/svc-open/web-service-v2";
            $soapClient = new SoapClient($url);
            $params = [
                'paramsJson'    =>  '{"page":1,"pageSize":50,"getDetail":1,"getAddress":1,"getCustomOrderType":1,"condition":{"idDesc":1'. $code . '}}',
                'userName'      =>  "NJJ",
                'userPass'      =>  "alex02081888",
                'service'       =>  "getOrderList"
            ];
            $ret = $soapClient->callService($params);
            $retArr = get_object_vars($ret);
            $retJson = $retArr['response'];
            $result = json_decode($retJson, true);
            if ($result['code'] != "200") {
                throw new Exception("error code: " . $result['code'] . "(" . $result['message'] . ")");
            }

            $data = $result['data'];
            $count = 0;
            foreach ($data as $key => $item) {
                $count++;
                $isLast = $count == count($data) ? $key + 1 : 0;
                $isPageUp = $count == Config::get('order_page_num');
                OrderModel::orderSave($isLast, $isPageUp, $item);
            }
            Db::commit();
            echo "success";
        } catch (\SoapFault $e) {
            Db::rollback();
            dump('SoapFault:'.$e);
        } catch (\Exception $e) {
            Db::rollback();
            dump('Exception:'.$e);
        }
    }

    public function productSave()
    {
        header("content-type:text/html;charset=utf-8");
        Db::startTrans();
        try {
            for ($i = 1; $i < 30; $i ++) {
                $url = "https://nt5e7hf.eccang.com/default/svc-open/web-service-v2";
                $soapClient = new SoapClient($url);
                $params = [
                    'paramsJson'    =>  '{"page":' . $i . '}',
                    'userName'      =>  "NJJ",
                    'userPass'      =>  "alex02081888",
                    'service'       =>  "getProductList"
                ];
                $ret = $soapClient->callService($params);
                $retArr = get_object_vars($ret);
                $retJson = $retArr['response'];
                $result = json_decode($retJson, true);
                if ($result['code'] != "200") {
                    throw new Exception("error code: " . $result['code'] . "(" . $result['message'] . ")");
                }

                $data = $result['data'];
                if (count($data) <= 0) {
                    break;
                }

                foreach ($data as $item) {
                    $productInfo = ProductModel::get(['productSku' => $item['productSku']]);
                    if (!empty($productInfo)) {
                        continue;
                    }
                    $productDetail = $item;
                    unset($productDetail['productPackage']);
                    unset($productDetail['productCost']);
                    $productDetail['productPackage'] = $item['productPackage'];
                    $productDetail['productCost'] = $item['productCost'];
                    $newId = ProductModel::create($productDetail)->getLastInsID();
                    if (empty($newId)) {
                        throw new Exception("产品插入失败！");
                    }
                }
            }
            Db::commit();

            echo "success";
        } catch (\SoapFault $e) {
            Db::rollback();
            dump('SoapFault:'.$e);
        } catch (\Exception $e) {
            Db::rollback();
            dump('Exception:'.$e);
        }
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws Exception
     */
    public function calculate()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            foreach ($post['id'] as $item) {
                if (!OrderModel::orderId2DeliverParams($item)) {
                    continue;
                }
            }
            echo json_encode(['code' => 1, 'msg' => '测算完成']);
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
        }
        exit;
    }
}
