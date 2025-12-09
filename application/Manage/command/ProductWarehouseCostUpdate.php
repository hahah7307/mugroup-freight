<?php
namespace app\Manage\command;

use app\Manage\model\AHS;
use app\Manage\model\ProductModel;
use app\Manage\model\ProductWarehouseCostUpdateModel;
use app\Manage\model\ProductWarehouseCostModel;
use app\Manage\model\StorageAhsRuleModel;
use app\Manage\model\StorageAreaModel;
use app\Manage\model\StorageBaseModel;
use app\Manage\model\StorageModel;
use app\Manage\model\StorageOutboundModel;
use app\Manage\model\StoragePeakSurchargeModel;
use app\Manage\model\StorageResidentialModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;

class ProductWarehouseCostUpdate extends Command
{
    protected function configure()
    {
        $this->setName('ProductWarehouseCostUpdate')->setDescription('Here is the ProductWarehouseCostUpdate');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');
        Config::load(APP_PATH . 'warehouse_cost.php');

        $updateObj = new ProductWarehouseCostUpdateModel();
        $warehouseCreate = $updateObj->find(1);

        Db::startTrans();
        try {
            $costItem = [];
            $offsetProductList = [];
            $productObj = new ProductModel();
            $offsetProduct = $productObj->where(['saleStatus' => 2])->limit($warehouseCreate['offset'], $warehouseCreate['page_num'])->select();
            if (count($offsetProduct) <= 0) {
                $updateObj->update(['id' => 1, 'offset' => 0]);
                echo 'restart';exit();
            } else {
                $costObj = new ProductWarehouseCostModel();
                $warehouseArea = StorageAreaModel::all();
                foreach ($offsetProduct as $productItem) {
                    $count = $costObj->where(['product_sku' => $productItem['productSku']])->count();
                    if ($count > 0) {
                        $offsetProductList[] = $productItem['productSku'];
                        foreach ($warehouseArea as $area) {
                            $costItem[] = self::generateProductSkuCost($productItem['productSku'], $area['storage_code'], 1);
                            $costItem[] = self::generateProductSkuCost($productItem['productSku'], $area['storage_code'], 0);
                        }
                    }
                }

                if ($offsetProductList) {
                    $costObj->where(['product_sku' => ['in', $offsetProductList]])->delete();
                }

                if ($costObj->insertAll($costItem)) {
                    $updateObj->update(['id' => 1, 'offset' => $warehouseCreate['offset'] + $warehouseCreate['page_num']]);
                } else {
                    throw new Exception("更新失败");
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
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    static public function generateProductSkuCost($sku, $warehouse_code, $is_peak): array
    {
        $productObj = new ProductModel();
        $product_sku = $productObj->where(['productSku' => $sku])->find();
        $volume = round($product_sku['productLength'] * $product_sku['productWidth'] * $product_sku['productHeight'] / 1000000, 6);
        $gross_weight = ceil($product_sku['productWeight'] * 2.204);
        $volume_weight = ceil($product_sku['productLength'] * $product_sku['productWidth'] * $product_sku['productHeight'] / 4097);
        $is_ahs_d = AHS::AHSDimension($product_sku['productLength'], $product_sku['productWidth'], $product_sku['productHeight']) ? 1 : 0;
        $is_ahs_w = AHS::AHSWeight(ceil($product_sku['productWeight'] * 2.204)) ? 1 : 0;
        $is_os = AHS::OSFedex($product_sku['productLength'], $product_sku['productWidth'], $product_sku['productHeight'], $product_sku['productWeight']) ? 1 : 0;

        $areaObj = new StorageAreaModel();
        $area = $areaObj->where(['storage_code' => $warehouse_code])->find();
        $first_leg_fee = Config::get($warehouse_code) * $volume / 66;
        $base_freight_fee = self::productSku2Base($warehouse_code, $product_sku, $is_ahs_d, $is_ahs_w);
        $ahs_fee = self::AHS($area['storage_id'], $is_ahs_d, $is_ahs_w, $is_peak);
        $rds_fee = self::RDS($area['storage_id'], $is_peak);
        $outbound_fee = self::outbound($area['storage_id'], $gross_weight, $volume_weight);
        $warehouse_rent_fee = self::warehouseRent($warehouse_code, $volume);
        $cost_total = $first_leg_fee + $base_freight_fee + $ahs_fee + $rds_fee + $outbound_fee + $warehouse_rent_fee;
        return [
            'product_sku'	        =>	$sku,
            'warehouse_id'	        =>	$area['warehouseId'],
            'warehouse_code'	    =>	$warehouse_code,
            'is_peak'	            =>	$is_peak,
            'weight'	            =>	$product_sku['productWeight'],
            'length'	            =>	$product_sku['productLength'],
            'width'	                =>	$product_sku['productWidth'],
            'height'	            =>	$product_sku['productHeight'],
            'gross_weight'	        =>	$gross_weight,
            'volume_weight'	        =>	$volume_weight,
            'is_ahs_d'	            =>	$is_ahs_d,
            'is_ahs_w'	            =>	$is_ahs_w,
            'is_os'	                =>	$is_os,
            'first_leg_fee'	        =>	$first_leg_fee,
            'devanning_fee'	        =>	0.516351119,
            'base_freight_fee'	    =>	$base_freight_fee,
            'ahs_fee'	            =>	$ahs_fee,
            'rds_fee'	            =>	$rds_fee,
            'outbound_fee'	        =>	$outbound_fee,
            'warehouse_rent_fee'    =>	$warehouse_rent_fee,
            'cost_total'	        =>	$cost_total,
        ];
    }

    /**
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    static public function productSku2Base($warehouse_code, $product_sku, $is_ahs_d, $is_ahs_w)
    {
        $gross_weight = ceil($product_sku['productWeight'] * 2.204);
        $volume_weight = ceil($product_sku['productLength'] * $product_sku['productWidth'] * $product_sku['productHeight'] / 4097);
        $lbs = max($volume_weight, $gross_weight);
        if ($is_ahs_d + $is_ahs_w > 0) {
            $lbs = max($lbs, 40);
        }

        $areaObj = new StorageAreaModel();
        $area = $areaObj->where(['storage_code' => $warehouse_code])->find();
        $condition['storage_id'] = $area['storage_id'];
        $condition['lbs_weight'] = $lbs;
        $condition['zone'] = 6;
        $condition['state'] = StorageBaseModel::STATE_ACTIVE;
        $condition['start_at'] = ['lt', date('Y-m-d H:i:s')];
        $condition['end_at'] = ['egt', date('Y-m-d H:i:s')];
        return StorageBaseModel::get($condition)->getData('value');
    }

    /**
     * @throws DbException
     */
    static public function AHS($storage, $is_ahs_d, $is_ahs_w, $is_peak, $zone = 6)
    {
        $ahsFee = 0;
        if ($storage == StorageModel::LECANGID) {
            $ahsFee = self::AHSFeeLoctek($zone, $is_ahs_d, $is_ahs_w, $is_peak);
        } elseif ($storage == StorageModel::WUYOUDAID) {
            $ahsFee = self::AHSFeeWuyouda($zone, $is_ahs_d, $is_ahs_w, $is_peak);
        }
        return $ahsFee;
    }

    /**
     * @throws DbException
     */
    static public function AHSFeeLoctek($zone, $is_ahs_d, $is_ahs_w, $is_peak)
    {
        $storage = StorageModel::LECANGID;
        if ($is_ahs_w) {
            $weightFee = self::AHSFee($storage, 4, $zone);
        } elseif ($is_ahs_d) {
            $weightFee = self::AHSFee($storage, 5, $zone);
        } else {
            $weightFee = 0;
        }

        if ($is_peak && $weightFee) {
            return $weightFee + self::AHSPeakSurcharge($storage);
        } else {
            return $weightFee;
        }
    }

    /**
     * @throws DbException
     */
    static public function AHSFeeWuyouda($zone, $is_ahs_d, $is_ahs_w, $is_peak)
    {
        $storage = StorageModel::WUYOUDAID;
        if ($is_ahs_w) {
            $weightFee = self::AHSFee($storage, 6, $zone);
        } elseif ($is_ahs_d) {
            $weightFee = self::AHSFee($storage, 7, $zone);
        } else {
            $weightFee = 0;
        }

        if ($is_peak && $weightFee) {
            return $weightFee + self::AHSPeakSurcharge($storage);
        } else {
            return $weightFee;
        }
    }

    // 获取ahs费用
    /**
     * @throws DbException
     */
    static public function AHSFee($storage, $ahs_id, $zone)
    {
        $condition['storage_id'] = $storage;
        $condition['ahs_id'] = $ahs_id;
        $condition['state'] = 1;
        $condition['zone'] = $zone;
        $condition['start_at'] = ['lt', date('Y-m-d H:i:s')];
        $condition['end_at'] = ['egt', date('Y-m-d H:i:s')];
        return StorageAhsRuleModel::get($condition)->getData('value');
    }

    /**
     * @throws DbException
     */
    static public function AHSPeakSurcharge($storage)
    {
        $condition['storage_id'] = $storage;
        $condition['state'] = StoragePeakSurchargeModel::STATE_ACTIVE;
        $condition['type'] = 1;
        $condition['start_at'] = ['lt', date('Y-m-d H:i:s')];
        $condition['end_at'] = ['egt', date('Y-m-d H:i:s')];
        return StoragePeakSurchargeModel::get($condition)->getData('value');
    }

    /**
     * @throws DbException
     */
    static public function RDS($storage, $is_peak)
    {
        $condition['storage_id'] = $storage;
        $condition['state'] = StorageResidentialModel::STATE_ACTIVE;
        $condition['deliver_type'] = 'HD';
        $condition['start_at'] = ['lt', date('Y-m-d H:i:s')];
        $condition['end_at'] = ['egt', date('Y-m-d H:i:s')];
        $rdsFee = StorageResidentialModel::get($condition)->getData('value');

        if ($is_peak) {
            return $rdsFee + self::RDSPeakSurcharge($storage);
        } else {
            return $rdsFee;
        }
    }

    /**
     * @throws DbException
     */
    static public function RDSPeakSurcharge($storage)
    {
        $condition['storage_id'] = $storage;
        $condition['state'] = StoragePeakSurchargeModel::STATE_ACTIVE;
        $condition['type'] = 2;
        $condition['start_at'] = ['lt', date('Y-m-d H:i:s')];
        $condition['end_at'] = ['egt', date('Y-m-d H:i:s')];
        return StoragePeakSurchargeModel::get($condition)->getData('value');
    }

    /**
     * @throws DbException
     */
    static public function outbound($storage, $gross_weight, $volume_weight)
    {
        $platform = 'amazon';
        $storageOutbound = new StorageOutboundModel();
        $condition['state'] = 1;
        $condition['storage_id'] = $storage;
        $condition['platform_tag'] = $platform;
        $condition['start_at'] = ['lt', date('Y-m-d H:i:s')];
        $condition['end_at'] = ['egt', date('Y-m-d H:i:s')];
        $outboundList = $storageOutbound->where($condition)->order('level asc')->select();
        $price = 0;
        foreach ($outboundList as $rule) {
            $ruleCondition = json_decode($rule['condition'], true);
            $lbs = 0;
            // 出库费良仓和无忧达取计费重，乐歌取实重
            if ($storage == StorageModel::LIANGCANGID || $storage == StorageModel::WUYOUDAID) {
                $lbs = max($volume_weight, $gross_weight);
            } elseif ($storage == StorageModel::LECANGID) {
                $lbs = $gross_weight;
            }
            if ($ruleCondition['max'] == 0 && $lbs > $ruleCondition['min']) {
                $price = $rule['value'];
                break;
            } elseif ($lbs > $ruleCondition['min'] && $lbs <= $ruleCondition['max']) {
                $price = $rule['value'];
                break;
            }
            unset($lbs);
            unset($rule);
        }
        return $price;
    }

    /**
     * @throws ModelNotFoundException
     * @throws DbException
     * @throws DataNotFoundException
     */
    static public function warehouseRent($warehouse_code, $volume)
    {
        $areaObj = new StorageAreaModel();
        $area = $areaObj->where(['storage_code' => $warehouse_code])->find();
        if ($area['storage_id'] == StorageModel::LECANGID) {
            if ($warehouse_code == "SAV" || strpos($warehouse_code, "HOU")) {
                return round($volume * 60 * 0.3, 6);
            } else {
                return round($volume * 30 * 0.2 + $volume * 60 * 0.3, 6);
            }
        } elseif ($area['storage_id'] == StorageModel::WUYOUDAID) {
            return  round($volume * 60 * 0.4, 6);
        } else {
            return 0;
        }
    }
}