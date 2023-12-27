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
    public function index()
    {
        // 订单列表
        $order = new OrderModel();
        $list = $order->with(['details.product','address'])->order('id asc')->paginate(Config::get('PAGE_NUM'));
        $this->assign('list', $list);

        Session::set(Config::get('BACK_URL'), $this->request->url(), 'manage');
        return view();
    }

    /**
     * @throws Exception
     */
    public function orderSave()
    {
        header("content-type:text/html;charset=utf-8");
        Db::startTrans();
        try {
            $url = "http://nt5e7hf-eb.eccang.com/default/svc-open/web-service-v2";
            $soapClient = new SoapClient($url);
            $params = [
                'paramsJson'    =>  '{"page":1,"pageSize":50,"getDetail":1,"getAddress":1,"getCustomOrderType":1,"condition":{"idDesc":1}}',
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

            foreach ($data as $item) {
                $order = $item;
                unset($order['orderDetails']);
                unset($order['orderAddress']);
                $newId = OrderModel::create($order)->getLastInsID();
                if ($newId) {
                    $orderDetail = $item['orderDetails'];
                    foreach ($orderDetail as $detail) {
                        $detail['warehouseSkuList'] = json_encode($detail['warehouseSkuList']);
                        $detail['promotionIdList'] = json_encode($detail['promotionIdList']);
                        $detail['order_id'] = $newId;
                        $detailId = OrderDetailModel::create($detail)->getLastInsID();
                        if (empty($detailId)) {
                            throw new Exception("订单详情插入失败！");
                        }
                    }

                    $address = $item['orderAddress'];
                    $address['order_id'] = $newId;
                    $addressId = OrderAddressModel::create($address)->getLastInsID();
                    if (empty($addressId)) {
                        throw new Exception("订单地址插入失败！");
                    }
                } else {
                    throw new Exception("订单插入失败！");
                }
            }
            Db::commit();

//            $url = "https://nt5e7hf.eccang.com/default/svc-open/web-service-v2";
//            $soapClient = new SoapClient($url);
//            $params = [
//                'paramsJson'    =>  '{"productSkuLike":"NFP012GR"}',
//                'userName'      =>  "NJJ",
//                'userPass'      =>  "alex02081888",
//                'service'       =>  "getProductList"
//            ];
//            $ret = $soapClient->callService($params);
//            $arr = get_object_vars($ret);
//            dump(json_decode($arr['response'], true));
//            exit;

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
     */
    public function calculate()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();

            foreach ($post['id'] as $item) {
                $order = new OrderModel();
                $order = $order->with(['details.product', 'address', 'area'])->where(['id'=>$item])->find();
//                $order = OrderModel::with(['details.product','address'])->where(['id'=>$item])->select();
//                dump($order->toArray());
                $storage_id = $order['area']['storage_id'];
                $storage_type = $order['area']['type'];
                $zip_code = $order['address']['postalCode'];
                $product = array();
                foreach ($order['details'] as $detail) {
                    $product[] = [
                        'productWeight' =>  $detail['product']['productWeight'],
                        'productLength' =>  $detail['product']['productLength'],
                        'productWidth' =>  $detail['product']['productWidth'],
                        'productHeight' =>  $detail['product']['productHeight'],
                        'productQty' =>  $detail['qty']
                    ];
                }
                dump($storage_id);
                dump($storage_type);
                dump($zip_code);
                dump($product);

                // 计算体积重
                $fee = OrderModel::calculateDeliver($storage_id, $storage_type, $zip_code, $product);

                unset($product);
            }
        } else {
            echo json_encode(['code' => 0, 'msg' => '异常操作']);
            exit;
        }
    }
}
