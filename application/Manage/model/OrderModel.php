<?php

namespace app\Manage\model;

use SoapClient;
use think\Config;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\Model;

class OrderModel extends Model
{
    protected $name = 'ecang_order';

    protected $resultSetType = 'collection';

    protected function setCreatedAtAttr()
    {
        return date('Y-m-d H:i:s');
    }

    protected function setUpdatedAtAttr()
    {
        return date('Y-m-d H:i:s');
    }

    public function details(): \think\model\relation\HasMany
    {
        return $this->hasMany('OrderDetailModel', 'order_id');
    }

    public function address(): \think\model\relation\HasOne
    {
        return $this->hasOne('OrderAddressModel', 'order_id');
    }

    public function area(): \think\model\relation\HasOne
    {
        return $this->hasOne('StorageAreaModel', 'storage_code', 'warehouseCode');
    }

    // 根据订单获取计算运费所需数据

    /**
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws Exception
     */
    static public function orderId2DeliverParams($orderId)
    {
        $order = new OrderModel();
        $orderInfo = $order->with(['details.product', 'address', 'area'])->where(['id'=>$orderId])->find();
        $storage_id = $orderInfo['area']['storage_id'];
        if (empty($storage_id)) {
            return false; // 匹配不到仓库跳过
        }
        $storage_type = $orderInfo['area']['type'];
        $zip_code = $orderInfo['address']['postalCode'];
        if (empty($zip_code)) {
            return false; // 空邮编跳过
        }
        $product = array();
        foreach ($orderInfo['details'] as $detail) {
            $product[] = [
                'productWeight' =>  $detail['product']['productWeight'],
                'productLength' =>  $detail['product']['productLength'],
                'productWidth' =>  $detail['product']['productWidth'],
                'productHeight' =>  $detail['product']['productHeight'],
                'productQty' =>  $detail['qty']
            ];
        }

        // 计算运费和公式
        $deliverInfo = self::calculateDeliver($storage_id, $storage_type, $zip_code, $product, $orderInfo);
        if (empty($deliverInfo)) {
            return false; // 空zone跳过
        }
        $orderInfo['calcuInfo'] = $deliverInfo['label'];
        $orderInfo['calcuRes'] = $deliverInfo['fee'];
        $orderInfo['postalFormat'] = $deliverInfo['postalCode'];
        $orderInfo['zoneFormat'] = $deliverInfo['zone'];
        $orderInfo['charged_weight'] = $deliverInfo['charged_weight'];
        $orderInfo['base'] = $deliverInfo['base'];
        $orderInfo['ahs'] = $deliverInfo['ahs'];
        $orderInfo['ahsds'] = $deliverInfo['ahsds'];
        $orderInfo['das'] = $deliverInfo['das'];
        $orderInfo['outbound'] = $deliverInfo['outbound'];
        $orderInfo['fuelCost'] = $deliverInfo['fuelCost'];

        // 更新数据
        unset($orderInfo['details']);
        unset($orderInfo['address']);
        unset($orderInfo['area']);
        unset($product);
        return $order->update($orderInfo->toArray());
    }

    // 计算运费

    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    static public function calculateDeliver($storage, $type, $postalCode, $product, $order)
    {
        Config::load(APP_PATH . 'storage.php');

        $postalCode = self::postalFormat($postalCode);
        $lbs = StorageBaseModel::getProductLbs($storage, $product);

        // 出库费运算
        $outbound = StorageOutboundModel::getOutbound($storage, $product, $order['platform']);
        $platform = StorageOutboundModel::outboundPlatform();
        if (in_array($order['platform'], $platform)) {
            return [
                'label'             =>  "",
                'fee'               =>  $outbound,
                'charged_weight'    =>  $lbs,
                'postalCode'        =>  $postalCode,
                'zone'              =>  0,
                'base'              =>  0,
                'ahs'               =>  0,
                'ahsds'             =>  0,
                'das'               =>  0,
                'outbound'          =>  $outbound,
                'fuelCost'          =>  0
            ];
        }

        // 基础费运算
        $customerZone = StorageZoneModel::getCustomZone($storage, $type, $postalCode);
        if ($customerZone == 0) {
            return false;
        }
        $baseInfo = StorageBaseModel::get(['storage_id' => $storage, 'state' => 1, 'lbs_weight' => $lbs, 'zone' => $customerZone]);
        $base = $baseInfo ? $baseInfo['value'] : 0;

        // AHS运算 & AHS旺季附加费
        $ahs = AHS::getAHSFee($storage, $customerZone, $product);
        $ahsDemandSurcharges = $ahs ? AHS::demandSurcharges($storage, $order['datePaidPlatform']) : 0;

        // 偏远地址附加费
        $das = StorageDasModel::get(['storage_id' => $storage, 'state' => 1, 'zip_code' => $postalCode]);
        $dasFee = !empty($das) ? StorageDasFeeModel::get(['storage_id' => $storage, 'type' => $das['type']])->getData('value') : 0;

        // 住宅地址附加费
        $rdcFee = $order['rdcFee'];

        // 住宅旺季附加费
        $drdcFee = $order['drdcFee'];

        // 燃油费运算
        $fuel_cost = round(($base + $ahs + $dasFee + $rdcFee + $ahsDemandSurcharges + $drdcFee) * Config::get('fuel_cost') * 0.01, 2);

        // 运费总计
        $label = $outbound . "(出库) + " . $base . "(基础) + " . $ahs . "(AHS) + " . $dasFee . "(偏远) + " . $rdcFee . "(住宅) + " . $ahsDemandSurcharges . "(AHS旺季) + " . $drdcFee . "(住宅旺季) + "  .$fuel_cost . "(燃油)";
        $price = round($outbound + $base + $ahs + $dasFee + $rdcFee + $ahsDemandSurcharges + $drdcFee + $fuel_cost, 2);

        return [
            'label'             =>  $label,
            'fee'               =>  $price,
            'charged_weight'    =>  $lbs,
            'postalCode'        =>  $postalCode,
            'zone'              =>  $customerZone,
            'base'              =>  $base,
            'ahs'               =>  $ahs,
            'ahsds'             =>  $ahsDemandSurcharges,
            'das'               =>  $dasFee,
            'outbound'          =>  $outbound,
            'fuelCost'          =>  $fuel_cost
        ];
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    static public function orderSave($isLast, $isPageUp, $item): bool
    {
        $orderPage = new OrderPageModel();
        $orderPageData = $orderPage->where('id', 1)->find();
        $page = $orderPageData['page'] + 1;

        if ($isPageUp) {
            $orderPage->save(['page' => $page, 'index' => 0], ['id' => $orderPageData['id']]);
        } elseif ($isLast) {
            $orderPage->save(['index' => $isLast - 1], ['id' => $orderPageData['id']]);
        }

        $order = $item;
        unset($order['orderDetails']);
        unset($order['orderAddress']);
        if (OrderModel::get(['saleOrderCode' => $item['saleOrderCode']])) {
            return false;
        }

        Db::startTrans();
        try {
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

                OrderModel::orderId2DeliverParams($newId);
            } else {
                throw new Exception("订单插入失败！");
            }

            file_put_contents( APP_PATH . '/../runtime/log/OrderCapture-' . date('Y-m-d') . '.log', PHP_EOL . "[" . date('Y-m-d H:i:s') . "] : " . var_export($order['order_id'] . '-' . $order['saleOrderCode'] . " : Calculate Success",TRUE), FILE_APPEND);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * @throws DbException
     * @throws Exception
     */
    static public function orderUpdate($item): bool
    {
        $order = $item;
        unset($order['orderDetails']);
        unset($order['orderAddress']);
        $orderItem = OrderModel::get(['saleOrderCode' => $item['saleOrderCode']]);
        if (empty($orderItem)) {
            return false;
        }

        Db::startTrans();
        try {
            // update detail
            $orderDetail = $item['orderDetails'];
            $detailItem = OrderDetailModel::all(['order_id' => $orderItem['id']]);
            foreach ($orderDetail as $key => $detail) {
                $detail['warehouseSkuList'] = json_encode($detail['warehouseSkuList']);
                $detail['promotionIdList'] = json_encode($detail['promotionIdList']);
                $detail['id'] = $detailItem[$key]['id'];
                OrderDetailModel::update($detail);
            }

            // 保留地址数据保留邮编，用于就算尾程
            // update address
//            $address = $item['orderAddress'];
//            $addressItem = OrderAddressModel::get(['order_id' => $orderItem['id']]);
//            $address['id'] = $addressItem['id'];
//            OrderAddressModel::update($address);

            $order['id'] = $orderItem['id'];
            OrderModel::update($order);
            OrderModel::orderId2DeliverParams($orderItem['id']);

            file_put_contents( APP_PATH . '/../runtime/log/OrderUpdate-' . date('Y-m-d') . '.log', PHP_EOL . "[" . date('Y-m-d H:i:s') . "] : " . var_export($order['order_id'] . '-' . $order['saleOrderCode'] . " : Update Success",TRUE), FILE_APPEND);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    static protected function postalFormat($postalCode)
    {
        return substr(trim($postalCode), 0, 5);
    }

    static public function saleOrderCodes2Order($code)
    {
        $url = "http://nt5e7hf-eb.eccang.com/default/svc-open/web-service-v2";
        $soapClient = new SoapClient($url);
        $params = [
            'paramsJson'    =>  '{"getDetail":1,"getAddress":1,"getCustomOrderType":1,"condition":{"saleOrderCodes":["' . $code . '"]}}',
            'userName'      =>  "NJJ",
            'userPass'      =>  "alex02081888",
            'service'       =>  "getOrderList"
        ];
        $ret = $soapClient->callService($params);
        file_put_contents( APP_PATH . '/../runtime/log/OrderCapture-' . date('Y-m-d') . '.log', PHP_EOL . "[" . date('Y-m-d H:i:s') . "] : " . var_export($ret,TRUE), FILE_APPEND);
        $retArr = get_object_vars($ret);
        $retJson = $retArr['response'];
        $result = json_decode($retJson, true);
        return $result['data'];
    }
}
