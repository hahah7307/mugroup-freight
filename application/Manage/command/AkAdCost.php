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
        $lastMonth = date('Ym', strtotime('-1 month'));
        if ($lastMonth == $adCostCreate['month']
            && $adCostCreate['is_finished'] == 1
        ) {
            echo "success";exit();
        }

        // 当日产品数据开始清表更新
        if ($lastMonth > $adCostCreate['month']) {
            AkAdCostCreateModel::update(['id' => $adCostCreate['id'], 'month' => $lastMonth, 'page' => 1, 'is_finished' => 0]);
            $adCostCreate['page'] = 1;
        }

        Db::startTrans();
        try {
            $akAdCostObj = new AkAdCostModel();
            $offset = $akAdCostObj->where(['reportDateMonth' => date('Y-m', strtotime('-1 month'))])->count();

            $params = [
                'offset'        =>  $offset,
                'length'        =>  $adCostCreate['pageSize'],
                'startDate'     =>  date('Y-m', strtotime('-1 month')),
                'endDate'       =>  date('Y-m', strtotime('-1 month')),
                'monthlyQuery'  =>  true
            ];
//            dump($params);exit();
            $akAdCostRes = AkOpenAPI::makeRequest("/bd/profit/report/open/report/msku/list", "POST", $params);
            $akAdCostData = $akAdCostRes['data'];
            if (count($akAdCostData['records']) <= 0) {
                AkAdCostCreateModel::update(['id' => $adCostCreate['id'], 'page' => $adCostCreate['page'] + 1, 'is_finished' => 1]);
            } else {
                $addData = [];
                foreach ($akAdCostData['records'] as $item) {
                    $addData[] = [
                        'totalSalesQuantity'        =>  $item['totalSalesQuantity'],
                        'totalAdsSales'             =>  $item['totalAdsSales'],
                        'totalAdsSalesQuantity'     =>  $item['totalAdsSalesQuantity'],
                        'totalSalesAmount'          =>  $item['totalSalesAmount'],
                        'totalSalesRefunds'         =>  $item['totalSalesRefunds'],
                        'totalFeeRefunds'           =>  $item['totalFeeRefunds'],
                        'totalAdsCost'              =>  $item['totalAdsCost'],
                        'totalStorageFee'           =>  $item['totalStorageFee'],
                        'totalSalesTax'             =>  $item['totalSalesTax'],
                        'totalCost'                 =>  $item['totalCost'],
                        'custom_id'                 =>  $item['id'],
                        'sid'                       =>  $item['sid'],
                        'reportDateMonth'           =>  $item['reportDateMonth'],
                        'postedDateLocale'          =>  $item['postedDateLocale'],
                        'msku'                      =>  $item['msku'],
                        'localSku'                  =>  $item['localSku'],
                        'principalRealname'         =>  $item['principalRealname'],
                        'productDeveloperRealname'  =>  $item['productDeveloperRealname'],
                        'currencyCode'              =>  $item['currencyCode'],
                    ];
                    unset($item);
                }
                unset($akAdCostData);
                $akAdCostObj->saveAll($addData);
                AkAdCostCreateModel::update(['id' => $adCostCreate['id'], 'page' => $adCostCreate['page'] + 1]);
                unset($addData);
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
}