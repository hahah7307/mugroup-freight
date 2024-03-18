<?php
namespace app\Manage\command;

use app\Manage\model\AkAdCostModel;
use app\Manage\model\AkOpenAPI;
use app\Manage\model\AkAdCostCreateModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class AkAdCost extends Command
{
    protected function configure()
    {
        $this->setName('AkAdCost')->setDescription('Here is the AkAdCost');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $akAdCostCreateObj = new AkAdCostCreateModel();
        $adCostCreate = $akAdCostCreateObj->find(1);
        if ($adCostCreate['is_finished'] == 1) {
            echo "success";exit();
        }

        Db::startTrans();
        try {
            $akAdCostObj = new AkAdCostModel();
            $offset = $akAdCostObj->where(['reportDateMonth' => date('Y-m', strtotime($adCostCreate['month'] . '01'))])->count();

            $params = [
                'offset'        =>  $offset,
                'length'        =>  $adCostCreate['pageSize'],
                'startDate'     =>  date('Y-m', strtotime($adCostCreate['month'] . '01')),
                'endDate'       =>  date('Y-m', strtotime($adCostCreate['month'] . '01')),
                'monthlyQuery'  =>  true
            ];
//            dump($params);exit();
            $akAdCostRes = AkOpenAPI::makeRequest("/bd/profit/report/open/report/msku/list", "POST", $params);
            $akAdCostData = $akAdCostRes['data'];

            $addData = [];
            foreach ($akAdCostData['records'] as $item) {
                $adCost = $akAdCostObj->where(['totalSalesQuantity' => $item['totalSalesQuantity'], 'totalSalesAmount' => $item['totalSalesAmount'] ,'totalAdsCost' => $item['totalAdsCost'], 'custom_id' => $item['id'], 'sid' => $item['sid'], 'postedDateLocale' => $item['postedDateLocale'], 'msku' => $item['msku']])->find();
                if ($adCost) {
                    continue;
                }
                $addData[] = [
                    'totalSalesQuantity'                    =>  $item['totalSalesQuantity'],
                    'totalAdsSales'                         =>  $item['totalAdsSales'],
                    'totalAdsSalesQuantity'                 =>  $item['totalAdsSalesQuantity'],
                    'totalSalesAmount'                      =>  $item['totalSalesAmount'],
                    'totalSalesRefunds'                     =>  $item['totalSalesRefunds'],
                    'totalFeeRefunds'                       =>  $item['totalFeeRefunds'],
                    'totalAdsCost'                          =>  $item['totalAdsCost'],
                    'totalStorageFee'                       =>  $item['totalStorageFee'],
                    'sharedFbaStorageFee'                   =>  $item['sharedFbaStorageFee'],
                    'longTermStorageFee'                    =>  $item['longTermStorageFee'],
                    'sharedLongTermStorageFee'              =>  $item['sharedLongTermStorageFee'],
                    'sharedFbaOverageFee'                   =>  $item['sharedFbaOverageFee'],
                    'fbaStorageFeeAccrual'                  =>  $item['fbaStorageFeeAccrual'],
                    'fbaStorageFeeAccrualDifference'        =>  $item['fbaStorageFeeAccrualDifference'],
                    'longTermStorageFeeAccrual'             =>  $item['longTermStorageFeeAccrual'],
                    'longTermStorageFeeAccrualDifference'   =>  $item['longTermStorageFeeAccrualDifference'],
                    'totalSalesTax'                         =>  $item['totalSalesTax'],
                    'totalCost'                             =>  $item['totalCost'],
                    'custom_id'                             =>  $item['id'],
                    'sid'                                   =>  $item['sid'],
                    'reportDateMonth'                       =>  $item['reportDateMonth'],
                    'postedDateLocale'                      =>  $item['postedDateLocale'],
                    'msku'                                  =>  $item['msku'],
                    'localSku'                              =>  $item['localSku'],
                    'principalRealname'                     =>  $item['principalRealname'],
                    'productDeveloperRealname'              =>  $item['productDeveloperRealname'],
                    'currencyCode'                          =>  $item['currencyCode'],
                    'adsSpCost'                             =>  $item['adsSpCost'],
                    'adsSbCost'                             =>  $item['adsSbCost'],
                    'adsSbvCost'                            =>  $item['adsSbvCost'],
                    'adsSdCost'                             =>  $item['adsSdCost'],
                ];
                unset($item);
            }
            $akAdCostObj->saveAll($addData);
            unset($addData);
            if (count($akAdCostData['records']) < $adCostCreate['pageSize']) {
                AkAdCostCreateModel::update(['id' => $adCostCreate['id'], 'page' => $adCostCreate['page'] + 1, 'is_finished' => 1]);
            } else {
                AkAdCostCreateModel::update(['id' => $adCostCreate['id'], 'page' => $adCostCreate['page'] + 1]);
            }

            unset($akAdCostData);
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
}