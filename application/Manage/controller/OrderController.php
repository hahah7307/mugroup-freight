<?php
namespace app\Manage\controller;

use app\Manage\model\OrderAddressModel;
use app\Manage\model\OrderDetailModel;
use app\Manage\model\OrderModel;
use app\Manage\model\ProductModel;
use PHPExcel_IOFactory;
use PHPExcel_Reader_Exception;
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
            $code = '"saleOrderCodes":["WEC0322310310056","wf-CS493446402","wf-CA490256588-1","WAL-0015-108830554985272","WAL-0015-108830555497775","WAL-0015-108830556126577","WAL-0015-108830556150466","WAL-0015-108830556197902","WAL-0015-108830556235667","WAL-0015-108830556447654","111-4993938-8145837","114-6650471-9181851","WAL-0015-108830656606570","WEC0202311010027","WEC0732311010026","112-1331038-7953855","111-8253014-9120241","113-6172109-7762643","112-8199898-0941002","112-8199898-0941002","113-0107617-8006662","WAL-0015-108830656762419","112-3752166-1517843","111-9353603-9485812","111-6369802-4590660","111-2690812-0133010","112-1828116-3325002-1","113-4924029-6045839","112-3417797-4629833","113-9391384-5302622","111-0457987-6445826","112-6713942-5516220","114-8264835-6565064","112-9195884-7063415","114-5410802-3461027","114-0377074-3146661","111-3105334-4455460","WEC0292311010016","111-1575017-9381868","112-2813454-9702619","WEC0242311010024","WEC0102311010025","112-4976561-7603451","114-0437864-5940213","112-4112901-2143468","112-5614476-8523448","112-5272070-7811446","wf-CS493579312","wf-CS493576996","wf-CS493573998"]';
//            $code = '';
            $url = "http://nt5e7hf-eb.eccang.com/default/svc-open/web-service-v2";
            $soapClient = new SoapClient($url);
            $params = [
                'paramsJson'    =>  '{"getDetail":1,"getAddress":1,"getCustomOrderType":1,"condition":{'. $code . '}}',
                'userName'      =>  "NJJ",
                'userPass'      =>  "alex02081888",
                'service'       =>  "getOrderList"
            ];
            $ret = $soapClient->callService($params);
            file_put_contents( APP_PATH . '/../runtime/log/test.log', PHP_EOL . "[" . date('Y-m-d H:i:s') . "] : " . var_export($ret,TRUE), FILE_APPEND);
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

    // 导入excel计算计费重差和最终费用
    /**
     * @throws DataNotFoundException
     * @throws PHPExcel_Reader_Exception
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws Exception
     */
    public function import()
    {
        // phpexcel
        require_once './static/classes/PHPExcel/Classes/PHPExcel.php';

        $filename = input('filename');
        $file= "./upload/excel/" . $filename;
        $excelReader = PHPExcel_IOFactory::createReaderForFile($file);
        $excelObj = $excelReader->load($file);
        $worksheet = $excelObj->getSheet(0);
        $data = $worksheet->toArray();

        if (count($data[0]) == Config::get('excel_col_liang')) {
            $this->importPostalCode($data, Config::get('excel_ordercode_liang'), Config::get('excel_postal_liang'));
            $this->setUnsetFee($data, Config::get('excel_ordercode_liang'), Config::get('excel_weight_liang'), Config::get('excel_rdc_liang'), Config::get('excel_drdc_liang'));
        } elseif (count($data[0]) == Config::get('excel_col_loctek')) {
            $this->importPostalCode($data, Config::get('excel_ordercode_loctek'), Config::get('excel_postal_loctek'));
            $this->setUnsetFee($data, Config::get('excel_ordercode_loctek'), Config::get('excel_weight_loctek'), Config::get('excel_rdc_loctek'), Config::get('excel_drdc_loctek'));
        } else {
            $this->error("请上传正确的表格", Session::get(Config::get('BACK_URL')));
        }

        $this->redirect(Session::get(Config::get('BACK_URL'), 'manage'));
    }

    // 某些无邮编的订单导入邮编
    public function importPostalCode($data, $orderCodeCol, $postalCodeCol): bool
    {
        $orderObject = new OrderModel();
        foreach ($data as $item) {
            $orderItem = $orderObject->with('address')->where(['saleOrderCode' => $item[$orderCodeCol]])->find();
            if (!empty($orderItem) && empty($orderItem['address']['postalCode'])) {
                $addressObj = new OrderAddressModel();
                $addressObj->save(['postalCode' => $this->postalCodeFormat($item[$postalCodeCol])], ['order_id' => $orderItem['id']]);
            }
        }
        return true;
    }

    // 客户订单邮编格式化
    public function postalCodeFormat($postalCode): string
    {
        $postalCode = strval($postalCode);
        if (strlen($postalCode) == 4) {
            return "0" . $postalCode;
        } elseif (strlen($postalCode) == 3) {
            return "00" . $postalCode;
        } else {
            return $postalCode;
        }
    }

    // 导入后更新以下字段
    // 是否导入（isImport） 住宅地址附加费（rdcFee） 住宅地址旺季附加费（drdcFee） 实际计费重与理论差值（diff_weight）
    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    public function setUnsetFee($data, $orderCodeCol, $weightCol, $rdfCol, $drdcCol): bool
    {
        $orderObject = new OrderModel();
        foreach ($data as $item) {
            $orderItem = $orderObject->where(['saleOrderCode' => $item[$orderCodeCol]])->find();
            if ($orderItem) {
                $order = new OrderModel();
                $diffWeight = intval($item[$weightCol] - $orderItem['charged_weight']);
                $drdcFee = !empty($item[$drdcCol]) ? $item[$drdcCol] : 0;
                $order->save(['rdcFee' => $item[$rdfCol], 'drdcFee' => $drdcFee, 'diff_weight' => $diffWeight, 'isImport' => 1], ['id' => $orderItem['id']]);

                OrderModel::orderId2DeliverParams($orderItem['id']);
            }
        }
        return true;
    }
}
