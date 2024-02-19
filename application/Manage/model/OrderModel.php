<?php

namespace app\Manage\model;

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
        $product = [
            'productWeight'     =>  $orderInfo['details'][0]['product']['productWeight'],
            'productLength'     =>  $orderInfo['details'][0]['product']['productLength'],
            'productWidth'      =>  $orderInfo['details'][0]['product']['productWidth'],
            'productHeight'     =>  $orderInfo['details'][0]['product']['productHeight'],
            'productQty'        =>  $orderInfo['details'][0]['qty']
        ];

        // 计算运费和公式
        $deliverInfo = self::calculateDeliver($storage_id, $storage_type, $zip_code, $product, $orderInfo);
        if (empty($deliverInfo)) {
            return false; // 空zone跳过
        }
        $orderInfo['calcuInfo'] = $deliverInfo['label'];
        $orderInfo['calcuRes'] = $deliverInfo['fee'] * $orderInfo['details'][0]['qty'];
        $orderInfo['postalFormat'] = $deliverInfo['postalCode'];
        $orderInfo['zoneFormat'] = $deliverInfo['zone'];
        $orderInfo['charged_weight'] = $deliverInfo['charged_weight'];
        $orderInfo['base'] = $deliverInfo['base'];
        $orderInfo['ahs'] = $deliverInfo['ahs'];
        $orderInfo['ahsds'] = $deliverInfo['ahsds'];
        $orderInfo['das'] = $deliverInfo['das'];
        $orderInfo['rdcFee'] = $deliverInfo['rdcFee'];
        $orderInfo['drdcFee'] = $deliverInfo['drdcFee'];
        $orderInfo['outbound'] = $deliverInfo['outbound'];
        $orderInfo['fuelCost'] = $deliverInfo['fuelCost'];
        $orderInfo['commission'] = $deliverInfo['commission'];

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
        // 获取计费重（不同仓库在同一值上会使用不同的重量）
        $lbs = StorageBaseModel::getProductLbs($storage, $product);

        // 基础费运算
        $customerZone = StorageZoneModel::getCustomZone($storage, $type, $postalCode);
        if ($customerZone == 0) {
            return false;
        }
        $baseInfo = StorageBaseModel::getBase($storage, $lbs, $customerZone, $order);
        $base = $baseInfo ? $baseInfo['value'] : 0;

        // 出库费运算
        $platform = StorageOutboundModel::outboundPlatform();
        $outbound = StorageOutboundModel::getOutbound($storage, $product, $order);
        if (in_array($order['platform'], $platform)) {
            return [
                'label'             =>  "",
                'fee'               =>  $outbound,
                'charged_weight'    =>  $lbs,
                'postalCode'        =>  $postalCode,
                'zone'              =>  $customerZone,
                'base'              =>  0,
                'ahs'               =>  0,
                'ahsds'             =>  0,
                'das'               =>  0,
                'rdcFee'            =>  0,
                'drdcFee'           =>  0,
                'outbound'          =>  $outbound,
                'fuelCost'          =>  0,
                'commission'        =>  0
            ];
        }

        // AHS运算 & AHS旺季附加费
        $ahs = AHS::getAHSFee($storage, $customerZone, $product, $order);
        $AHSPeakSurcharge = $ahs ? AHS::AHSPeakSurcharge($storage, $order) : 0;

        // 偏远地址附加费
        $das = StorageDasModel::get(['storage_id' => $storage, 'state' => 1, 'zip_code' => $postalCode]);
        $dasFee = !empty($das) ? StorageDasFeeModel::getDasFee($storage, $das, $order) : 0;

        // 住宅地址附加费 & 住宅旺季附加费
        $ResidentialFee = StorageResidentialModel::getResidential($storage, $order);
        $ResidentialPeakSurcharge = $ResidentialFee ? StorageResidentialModel::ResidentialPeakSurcharge($storage, $order) : 0;

        // 燃油费运算
        $fuel_cost = round(($base + $ahs + $dasFee + $ResidentialFee + $AHSPeakSurcharge + $ResidentialPeakSurcharge) * Config::get('fuel_cost') * 0.01, 2);

        // 佣金（过路费）
        if ($storage == StorageModel::LIANGCANGID) {
            $commission_rate = Config::get('lc_commission');
        } elseif ($storage == StorageModel::LECANGID) {
            $commission_rate = Config::get('le_commission');
        } else {
            $commission_rate = 0;
        }
        $commission = round(($base + $ahs + $dasFee + $ResidentialFee + $AHSPeakSurcharge + $ResidentialPeakSurcharge + $fuel_cost) * $commission_rate * 0.01, 2);

        // 运费总计
        $label = $outbound . "(出库) + " . $base . "(基础) + " . $ahs . "(AHS) + " . $dasFee . "(偏远) + " . $ResidentialFee . "(住宅) + " . $AHSPeakSurcharge . "(AHS旺季) + " . $ResidentialPeakSurcharge . "(住宅旺季) + "  .$fuel_cost . "(燃油) + "  .$commission . "(过路费)";
        $price = round($outbound + $base + $ahs + $dasFee + $ResidentialFee + $AHSPeakSurcharge + $ResidentialPeakSurcharge + $fuel_cost + $commission, 2);

        return [
            'label'             =>  $label,
            'fee'               =>  $price,
            'charged_weight'    =>  $lbs,
            'postalCode'        =>  $postalCode,
            'zone'              =>  $customerZone,
            'base'              =>  $base,
            'ahs'               =>  $ahs,
            'ahsds'             =>  $AHSPeakSurcharge,
            'das'               =>  $dasFee,
            'rdcFee'            =>  $ResidentialFee,
            'drdcFee'           =>  $ResidentialPeakSurcharge,
            'outbound'          =>  $outbound,
            'fuelCost'          =>  $fuel_cost,
            'commission'        =>  $commission
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

            file_put_contents( APP_PATH . '/../runtime/log/OrderUpdate-' . date('Y-m-d') . '.log', PHP_EOL . "[" . date('Y-m-d H:i:s') . "] : " . var_export($order['id'] . '-' . $order['order_id'] . '-' . $order['saleOrderCode'] . " : Update Success",TRUE), FILE_APPEND);
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

    /**
     * @throws \SoapFault
     */
    static public function saleOrderCodes2Order($code)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $apiRes = ApiClient::EcWarehouseApi(Config::get("ec_eb_uri"), "getOrderList", '{"getDetail":1,"getAddress":1,"getCustomOrderType":1,"condition":{"saleOrderCodes":["' . $code . '"]}}');
        if ($apiRes['code'] == 0) {
            return [];
        }
        return $apiRes['data'];
    }
}
