<?php

namespace app\Manage\model;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
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

    // 计算运费

    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    static public function calculateDeliver($storage, $type, $postalCode, $product): array
    {
        $price = 0;
        $label = "";

        // 基础费运算
        $postalCode = self::postalFormat($postalCode);
        $customerZone = StorageZoneModel::getCustomZone($storage, $type, $postalCode);
        $lbs = StorageBaseModel::getProductLbs($storage, $product);
        dump($storage);
        dump($lbs);
        dump($customerZone);
        $baseInfo = StorageBaseModel::get(['storage_id' => $storage, 'state' => 1, 'lbs_weight' => $lbs, 'zone' => $customerZone])->getData();
        $base = $baseInfo ? $baseInfo['value'] : 0;

        // AHS运算
        $ahs = AHS::getAHSFee($storage, $customerZone, $product);
        dump($ahs);

        // 偏远地址附加费
        $das = StorageDasModel::get(['storage_id' => $storage, 'state' => 1, 'zip_code' => $postalCode])->getData();
        $dasFee =StorageDasFeeModel::get(['storage_id' => $storage, 'type' => $das['type']])->getData();
        dump($dasFee);


        return ['price' => $price, 'label' => $label];
    }

    static protected function postalFormat($postalCode)
    {
        return substr(trim($postalCode), 0, 5);
    }
}
