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

//    protected $insert = ['created_at', 'updated_at'];

//    protected $update = ['updated_at'];

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
//                dump($storage_id);
//                dump($storage_type);
//                dump($zip_code);
//                dump($product);

        // 计算运费和公式
        $deliverInfo = self::calculateDeliver($storage_id, $storage_type, $zip_code, $product, $orderInfo);
        if (empty($deliverInfo)) {
            return false; // 空zone跳过
        }
        $orderInfo['calcuInfo'] = $deliverInfo['label'];
        $orderInfo['calcuRes'] = $deliverInfo['fee'];

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
        // 基础费运算
        $postalCode = self::postalFormat($postalCode);
        $customerZone = StorageZoneModel::getCustomZone($storage, $type, $postalCode);
//        dump($customerZone);
        if ($customerZone == 0) {
            return false;
        }
        $lbs = StorageBaseModel::getProductLbs($storage, $product);
//        dump("storage:" . $storage);
//        dump("lbs:" . $lbs);
//        dump("postalCode:" .$postalCode);
//        dump("customerZone:". $customerZone);
        $baseInfo = StorageBaseModel::get(['storage_id' => $storage, 'state' => 1, 'lbs_weight' => $lbs, 'zone' => $customerZone]);
        $base = $baseInfo ? $baseInfo['value'] : 0;
//        dump("base:" .$base);

        // AHS运算 & AHS旺季附加费
        $ahs = AHS::getAHSFee($storage, $customerZone, $product);
        $ahsDemandSurcharges = $ahs ? AHS::demandSurcharges($storage, $order['createdDate']) : 0;
//        dump("ahs:" .$ahs);
//        dump("ahsDemandSurcharges:" .$ahsDemandSurcharges);

        // 偏远地址附加费
        $das = StorageDasModel::get(['storage_id' => $storage, 'state' => 1, 'zip_code' => $postalCode]);
//        dump("dasType:" . $das['type']);
        $dasFee = !empty($das) ? StorageDasFeeModel::get(['storage_id' => $storage, 'type' => $das['type']])->getData('value') : 0;
//        dump("dasFee:" .$dasFee);

        // 住宅地址附加费
        $rdcFee = $order['rdcFee'];

        // 住宅旺季附加费
        $drdcFee = $order['drdcFee'];

        // 出库费运算
        $outbound = StorageOutboundModel::getOutbound($storage, $product, $order['platform']);
//        dump("outbound:" . $outbound);

        // 运费总计
        $label = $outbound . " + (" . $base . " + " . $ahs . " + " . $dasFee . " + " . $rdcFee . " + " . $ahsDemandSurcharges . " + " . $drdcFee . ") * 1." . Config::get('fuel_cost');
//        dump($label);
        $price = $outbound + ($base + $ahs + $dasFee + $rdcFee + $ahsDemandSurcharges + $drdcFee) * (1 + Config::get('fuel_cost') * 0.01);
//        dump($price);

        return ['fee' => $price, 'label' => $label];
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception
     */
    static public function orderSave($item): bool
    {
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

            file_put_contents( APP_PATH . '/../runtime/log/test.log', PHP_EOL . "[" . date('Y-m-d H:i:s') . "] : " . var_export($order['order_id'] . '-' . $order['saleOrderCode'] . " : Calculate Success",TRUE), FILE_APPEND);
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
}
