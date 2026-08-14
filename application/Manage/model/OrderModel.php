<?php

namespace app\Manage\model;

use SoapFault;
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

    public function __construct($data = [])
    {
        Config::load(APP_PATH . 'storage.php');

        parent::__construct($data);
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

        if (!in_array($orderInfo['status'], [4, 5])) {
            return false; // 订单未发货跳过
        }

        $storage_id = $orderInfo['area']['storage_id'];
        if (empty($storage_id)) {
            return false; // 匹配不到仓库跳过
        }

        $zip_code = $orderInfo['address']['postalCode'];
        if (empty($zip_code)) {
            return false; // 空邮编跳过
        }

        // 计算运费和公式
        if ($orderInfo['warehouseCode'] == 'EDA_UKBHS-B002') {
            $tail = self::calculateDeliver_uk($orderInfo);
        } elseif ($orderInfo['warehouseCode'] == 'WUYOUDA_DUSJW2') {
            $tail = self::calculateDeliver_de($orderInfo);
        } else {
            $tail = self::calculateDeliver($orderInfo);
        }
        if ($tail) {
            $orderInfo['postalFormat'] = $tail[0]['postal_format'];
            $orderInfo['zoneFormat'] = $tail[0]['zone_format'];
            $orderInfo['charged_weight'] = array_sum(array_column($tail, 'charged_weight'));
            $orderInfo['inbound'] = array_sum(array_column($tail, 'inbound'));
            $orderInfo['outbound'] = array_sum(array_column($tail, 'outbound'));
            $orderInfo['sfp'] = array_sum(array_column($tail, 'sfp'));
            $orderInfo['base'] = array_sum(array_column($tail, 'base'));
            $orderInfo['ahs'] = array_sum(array_column($tail, 'ahs'));
            $orderInfo['ahsds'] = array_sum(array_column($tail, 'ahs_pss'));
            $orderInfo['das'] = array_sum(array_column($tail, 'das'));
            $orderInfo['rdcFee'] = array_sum(array_column($tail, 'residential'));
            $orderInfo['drdcFee'] = array_sum(array_column($tail, 'residential_pss'));
            $orderInfo['signature'] = array_sum(array_column($tail, 'signature'));
            $orderInfo['fuelCost'] = array_sum(array_column($tail, 'fuel_cost'));
            $orderInfo['commission'] = array_sum(array_column($tail, 'commission'));
            $orderInfo['calcuRes'] = array_sum(array_column($tail, 'tail_course'));
            $orderInfo['calcuInfo'] = $orderInfo['outbound'] . "(出库) + " . $orderInfo['sfp'] . "(SFP) + " . $orderInfo['base'] . "(基础) + " . $orderInfo['ahs'] . "(AHS) + " . $orderInfo['das'] . "(偏远) + " . $orderInfo['rdcFee'] . "(住宅) + " . $orderInfo['ahsds'] . "(AHS旺季) + " . $orderInfo['drdcFee'] . "(住宅旺季) + "  . $orderInfo['signature'] . "(签名) + "  . $orderInfo['fuelCost'] . "(燃油) + "  .$orderInfo['commission'] . "(过路费)";
        }

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
    static public function calculateDeliver($order): array
    {
        $storage_id = $order['area']['storage_id'];

        $postalCode = self::postalFormat($order['address']['postalCode']);

        $orderDetailObj = new OrderDetailModel();
        $tail = [];
        foreach ($order['details'] as $detail) {
            // 获取计费重（不同仓库在同一值上会使用不同的重量）
            $lbs = StorageBaseModel::getProductLbs($storage_id, $detail);

            // 出库费运算
            $outbound = StorageOutboundModel::getOutbound($storage_id, $detail, $order);
            $platform = StorageOutboundModel::outboundPlatform();
            if (in_array($order['platform'], $platform)
                || ($order['platform'] == "semitemu" && $order['datePaidPlatform'] >= "2025-04-26 00:00:00")
                || empty($order['dateWarehouseShipping'])
                || $order['dateWarehouseShipping'] == '0000-00-00 00:00:00') {
                $tailData = [
                    'postal_format'     =>  $postalCode,
                    'zone_format'       =>  0,
                    'charged_weight'    =>  $lbs,
                    'outbound'          =>  $outbound * $detail['qty'],
                    'base'              =>  0,
                    'ahs'               =>  0,
                    'ahs_pss'           =>  0,
                    'das'               =>  0,
                    'residential'       =>  0,
                    'residential_pss'   =>  0,
                    'signature'         =>  0,
                    'fuel_cost'         =>  0,
                    'commission'        =>  0,
                    'tail_course'       =>  $outbound * $detail['qty']
                ];
                $orderDetailObj->update($tailData, ['id' => $detail['id']]);
                $tail[] = $tailData;
                continue;
            }

            // SFP
            $sfp = StorageSfpModel::getSFP($storage_id, $order);

            // 基础费运算
            $customerZone = StorageZoneModel::getCustomZone($order, $postalCode);
            if ($customerZone == 0) {
                continue;
            }
            $baseInfo = StorageBaseModel::getBase($storage_id, $lbs, $customerZone, $order, $detail);
            $base = $baseInfo ? $baseInfo['value'] : 0;

            // AHS运算 & AHS旺季附加费
            $ahs = AHS::getAHSFee($storage_id, $customerZone, $detail, $order);
            $AHSPeakSurcharge = $ahs ? AHS::AHSPeakSurcharge($storage_id, $order) : 0;

            // 偏远地址附加费
            $dasType = StorageDasModel::getDASType($storage_id, $postalCode, $order);
            $dasFee = !empty($dasType) ? StorageDasFeeModel::getDasFee($storage_id, $dasType, $order) : 0;

            // 住宅地址附加费 & 住宅旺季附加费
            $ResidentialFee = StorageResidentialModel::getResidential($storage_id, $order);
            $ResidentialPeakSurcharge = $ResidentialFee ? StorageResidentialModel::ResidentialPeakSurcharge($storage_id, $order) : 0;

            // 签名费
            if (!strpos($order['shippingMethod'], "-QIANMING") && !strpos($order['shippingMethod'], "-QM")) {
                $signature = 0;
            } else {
                $signatureData = StorageSignatureModel::getSignature($storage_id, $order);
                $signature = $signatureData ? $signatureData['value'] : 0;
            }

            // 燃油费运算
            $fuel_surcharge_rate = StorageFuelSurchargeRateModel::getFuelSurchargeRate($order);
            if (empty($fuel_surcharge_rate)) {
                continue;
            }
            if ($order['shippingMethod'] == "UPS_ROADIE_GROUND") {
                $fuel_surcharge_rate['value'] = 0;
            }
            $fuel_cost = round(($base + $ahs + $dasFee + $ResidentialFee + $AHSPeakSurcharge + $ResidentialPeakSurcharge + $signature) * $fuel_surcharge_rate['value'] * 0.01, 2);

            // 佣金（过路费）
            $commission_rate = StorageCommissionModel::getCommission($storage_id, $order);
            $commission = round(($base + $ahs + $dasFee + $ResidentialFee + $AHSPeakSurcharge + $ResidentialPeakSurcharge + $signature + $fuel_cost) * $commission_rate, 2);

            // 运费总计
            $price = round($outbound + $sfp + $base + $ahs + $dasFee + $ResidentialFee + $AHSPeakSurcharge + $ResidentialPeakSurcharge + $signature + $fuel_cost + $commission, 2);

            $tailData = [
                'postal_format'     =>  $postalCode,
                'zone_format'       =>  $customerZone,
                'charged_weight'    =>  $lbs,
                'sfp'               =>  $sfp,
                'outbound'          =>  $outbound,
                'base'              =>  $base,
                'ahs'               =>  $ahs,
                'ahs_pss'           =>  $AHSPeakSurcharge,
                'das'               =>  $dasFee,
                'residential'       =>  $ResidentialFee,
                'residential_pss'   =>  $ResidentialPeakSurcharge,
                'signature'         =>  $signature,
                'fuel_cost'         =>  $fuel_cost,
                'commission'        =>  $commission,
                'tail_course'       =>  $price * $detail['qty']
            ];
            $orderDetailObj->update($tailData, ['id' => $detail['id']]);
            $tail[] = $tailData;
        }

        return $tail;
    }

    // 计算运费
    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    static public function calculateDeliver_uk($order): array
    {
        $storage_id = $order['area']['storage_id'];

        $orderDetailObj = new OrderDetailModel();
        $tail = [];
        foreach ($order['details'] as $detail) {
            // 入库费运算
            $inbound = StorageInboundModel::getInbound($storage_id, $detail, $order);

            // 出库费运算
            $outbound = StorageOutboundModel::getOutbound($storage_id, $detail, $order, 'UK');

            // 基础费运算
            $base = StorageBaseUKModel::getBaseUK($storage_id, $order, $detail);

            // AHS运算 & AHS旺季附加费
            $ahs = 0;
            $AHSPeakSurcharge = 0;

            // 偏远地址附加费
            $dasFee = 0;

            // 住宅地址附加费 & 住宅旺季附加费
            $ResidentialFee = 0;
            $ResidentialPeakSurcharge = 0;

            // 签名费
            $signature = 0;

            // 燃油费运算
            if ($order['shippingMethod'] == "EDA_DPD_UK") {
                $fuel_surcharge_rate = 18.7;
            } elseif ($order['shippingMethod'] == "EDA_YODEL_48") {
                $fuel_surcharge_rate = 9;
            } else {
                $fuel_surcharge_rate = 0;
            }
            $fuel_cost = round(($base + $ahs + $dasFee + $ResidentialFee + $AHSPeakSurcharge + $ResidentialPeakSurcharge + $signature) * $fuel_surcharge_rate * 0.01, 2);

            // 佣金（过路费）
            $commission = 0;

            // 运费总计
            $price = round($inbound + $outbound + $base + $ahs + $dasFee + $ResidentialFee + $AHSPeakSurcharge + $ResidentialPeakSurcharge + $signature + $fuel_cost + $commission, 2);

            $tailData = [
                'postal_format'     =>  '',
                'zone_format'       =>  0,
                'charged_weight'    =>  '',
                'sfp'               =>  0,
                'inbound'           =>  $inbound,
                'outbound'          =>  $outbound,
                'base'              =>  $base,
                'ahs'               =>  $ahs,
                'ahs_pss'           =>  $AHSPeakSurcharge,
                'das'               =>  $dasFee,
                'residential'       =>  $ResidentialFee,
                'residential_pss'   =>  $ResidentialPeakSurcharge,
                'signature'         =>  $signature,
                'fuel_cost'         =>  $fuel_cost,
                'commission'        =>  $commission,
                'tail_course'       =>  $price * $detail['qty']
            ];
            $orderDetailObj->update($tailData, ['id' => $detail['id']]);
            $tail[] = $tailData;
        }

        return $tail;
    }

    // 计算运费
    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    static public function calculateDeliver_de($order): array
    {
        $storage_id = $order['area']['storage_id'];

        $orderDetailObj = new OrderDetailModel();
        $tail = [];
        foreach ($order['details'] as $detail) {
            // 出库费运算
            $outbound = StorageOutboundModel::getOutbound($storage_id, $detail, $order, 'DE');

            // 基础费运算
            $base = StorageBaseDEModel::getBaseDE($storage_id, $order, $detail);

            // AHS运算 & AHS旺季附加费
            $ahs = AHS::getAHSFeeDE($detail, $order);
            if ($order['shippingMethod'] == "DPD_CLASSIC_DE") {
                $AHSPeakSurcharge = 0.5;
            } else {
                $AHSPeakSurcharge = 0;
            }

            // 燃油费运算
            if ($order['shippingMethod'] == "DHL_PAKET") {
                $fuel_surcharge_rate = 3.25;
            } elseif ($order['shippingMethod'] == "DPD_CLASSIC_DE") {
                $fuel_surcharge_rate = 18;
                $fuel_surcharge_rate_dpd_emer = StorageFuelSurchargeRateDpdEmerModel::getFuelSurchargeRate($order);
                if (empty($fuel_surcharge_rate_dpd_emer)) {
                    $fuel_surcharge_rate += 0;
                } else {
                    $fuel_surcharge_rate += $fuel_surcharge_rate_dpd_emer['value'];
                }
            } else {
                $fuel_surcharge_rate = 0;
            }
            $fuel_cost = round(($base + $ahs + $AHSPeakSurcharge) * $fuel_surcharge_rate * 0.01, 2);

            // 运费总计
            $price = round($outbound + $base + $ahs + $AHSPeakSurcharge + $fuel_cost, 2);

            $tailData = [
                'postal_format'     =>  '',
                'zone_format'       =>  '',
                'charged_weight'    =>  0,
                'sfp'               =>  0,
                'inbound'           =>  0,
                'outbound'          =>  $outbound,
                'base'              =>  $base,
                'ahs'               =>  $ahs,
                'ahs_pss'           =>  $AHSPeakSurcharge,
                'das'               =>  0,
                'residential'       =>  0,
                'residential_pss'   =>  0,
                'signature'         =>  0,
                'fuel_cost'         =>  $fuel_cost,
                'commission'        =>  0,
                'tail_course'       =>  $price * $detail['qty']
            ];
            $orderDetailObj->update($tailData, ['id' => $detail['id']]);
            $tail[] = $tailData;
        }

        return $tail;
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    static public function orderSave($isLast, $isPageUp, $item): int
    {
        $orderPage = new OrderPageModel();
        $orderPageData = $orderPage->find(1);
        $page = $orderPageData['page'] + 1;

        if ($isPageUp) {
            $orderPage->save(['page' => $page, 'index' => 0], ['id' => $orderPageData['id']]);
        } elseif ($isLast) {
            $orderPage->save(['index' => $isLast - 1], ['id' => $orderPageData['id']]);
        }

        $order = $item;
        unset($order['orderDetails']);
        unset($order['orderAddress']);

        // 校验订单是否存在 存在即跳过
        $model = new OrderModel();
        $orderExist = $model->where(['order_id' => $item['order_id']])->find();
        if ($orderExist) {
            return $orderExist['id'];
        }

        Db::startTrans();
        try {
            // 新增订单数据
            $newId = $model->insertGetId($order);
            if ($newId) {
                // 新增订单详情
                $detailModel = new OrderDetailModel();
                $orderDetail = $item['orderDetails'];
                foreach ($orderDetail as $detail) {
                    $detail['warehouseSkuList'] = json_encode($detail['warehouseSkuList']);
                    $detail['promotionIdList'] = json_encode($detail['promotionIdList']);
                    $detail['buyerCustomizedInfo'] = json_encode($detail['buyerCustomizedInfo']);
                    $detail['order_id'] = $newId;
                    if (!$detailModel->insert($detail)) {
                        throw new Exception("订单详情插入失败！");
                    }
                }

                // 新增订单地址
                $addressModel = new OrderAddressModel();
                $address = $item['orderAddress'];
                $address['order_id'] = $newId;
                if (!$addressModel->insert($address)) {
                    throw new Exception("订单地址插入失败！");
                }
            } else {
                throw new Exception("订单插入失败！");
            }

            Db::commit();
            return $newId;
        } catch (\Exception $e) {
            dump($e->getMessage());
            Db::rollback();
            return 0;
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
        $model = new OrderModel();
        $orders = $model->where(['saleOrderCode' => $item['saleOrderCode']])->order('id asc')->select();
        if (empty($orders)) {
            return false;
        }

        // 删除易仓订单的重复数据
        $orderItem = [];
        foreach ($orders as $v) {
            // 有重复的易仓订单只留下最后一条，删除其余
            if ($v['order_id'] != $item['order_id']) {
                if (OrderModel::destroy($v['id'])) {
                    OrderAddressModel::destroy(['order_id' => $v['id']]);
                    OrderDetailModel::destroy(['order_id' => $v['id']]);
                }
            } else {
                if (!empty($orderItem)) {
                    if (OrderModel::destroy($orderItem['id'])) {
                        OrderAddressModel::destroy(['order_id' => $orderItem['id']]);
                        OrderDetailModel::destroy(['order_id' => $orderItem['id']]);
                        $orderItem = $v;
                    }
                } else {
                    $orderItem = $v;
                }
            }
        }

        // 删除后不存在可修改的易仓订单则返回，数据交给新增
        if (count($orderItem) == 0) {
            return false;
        }

        Db::startTrans();
        try {
            // 存在order_id一样的数据，操作更新
            // 获取订单详情，由于op_id不一定强绑定。所有直接删除原数据新增
            OrderDetailModel::destroy(['order_id' => $orderItem['id']]);

            // update detail
            $orderDetail = $item['orderDetails'];
            foreach ($orderDetail as $detail) {
                $detail['warehouseSkuList'] = isset($detail['warehouseSkuList']) ? json_encode($detail['warehouseSkuList']) : json_encode([]);
                $detail['promotionIdList'] = isset($detail['promotionIdList']) ? json_encode($detail['promotionIdList']) : json_encode([]);
                $detail['buyerCustomizedInfo'] = isset($detail['buyerCustomizedInfo']) ? json_encode($detail['buyerCustomizedInfo']) : json_encode([]);
                $detail['order_id'] = $orderItem['id'];
                OrderDetailModel::create($detail);
                unset($detail);
            }

            // update address
            // 保留地址数据保留邮编，用于计算尾程
            $isUpdateAddress = false;
            $addressModel = new OrderAddressModel();
            $addressData = $addressModel->where(['order_id' => $orderItem['id']])->select();
            foreach ($addressData as $addressDatum) {
                if (!empty($item['orderAddress']['postalCode']) && $addressDatum['postalCode'] != $item['orderAddress']['postalCode']) {
                    OrderAddressModel::destroy(['id' => $addressDatum['id']]);
                    $isUpdateAddress = true;
                }
            }
            unset($addressData);
            if ($isUpdateAddress) {
                $address = $item['orderAddress'];
                $address = array_filter($address);
                $address['order_id'] = $orderItem['id'];
                OrderAddressModel::create($address);
            }

            // 更新订单信息
            $order['id'] = $orderItem['id'];
            OrderModel::update($order);
            // 更新后计算订单尾程并更新
            OrderModel::orderId2DeliverParams($orderItem['id']);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            dump($e->getMessage());
            Db::rollback();
            return false;
        }
    }

    static protected function postalFormat($postalCode)
    {
        return substr(trim($postalCode), 0, 5);
    }

    /**
     * @throws SoapFault
     */
    static public function saleOrderCodes2Order($code)
    {
        $apiRes = ApiClient::EcWarehouseApi(Config::get("ec_eb_uri"), "getOrderList", '{"getDetail":1,"getAddress":1,"getCustomOrderType":1,"condition":{"saleOrderCodes":["' . $code . '"]}}');
        return $apiRes['code'] == 0 ? [] : $apiRes['data'];
    }
}
