<?php
namespace app\Manage\command;

use app\Manage\model\AkAmazonDailyListModel;
use app\Manage\model\AkAmazonDailyListPageModel;
use app\Manage\model\AkOpenAPI;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class AkAmazonDailyList extends Command
{
    protected function configure()
    {
        $this->setName('AkAmazonDailyList')->setDescription('Here is the AkAmazonDailyList');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        if (date('H') < 16) {
            echo "success";exit();
        }

        $pageObj = new AkAmazonDailyListPageModel();
        $list = $pageObj->where(['date' => ['lt', date('Ymd')]])->select();
        foreach ($list as $item) {
            $itemArr = $item->toArray();
            $itemArr['date'] = date('Ymd');
            $itemArr['offset'] = 0;
            $itemArr['length'] = 1000;
            $itemArr['is_finished'] = 0;
            $pageObj->update($itemArr, $itemArr['id']);
        }

        $page = $pageObj->where(['date' => date('Ymd'), 'is_finished' => 0])->order('id asc')->find();
        if ($page) {
            Db::startTrans();
            try {
                $params = [
                    'offset'        =>  $page['offset'],
                    'length'        =>  $page['length'],
                    'sid'           =>  $page['sid'],
                    'event_date'    =>  date('Y-m-d', strtotime("-1 day", time())),
                    'asin_type'     =>  2,
                    'type'          =>  2
                ];
                $akAmazonDailyListRes = AkOpenAPI::makeRequest("/erp/sc/data/sales_report/asinDailyLists", "POST", $params);
                $data = $akAmazonDailyListRes['data'];
                $listingObj = new AkAmazonDailyListModel();
                if (!empty($data)) {
                    $insertData = [];
                    foreach ($data as $listing) {
                        $listing['created_date'] = date('Ymd');
                        $insertData[] = $listing;
                    }
                    if ($listingObj->insertAll($insertData)) {
                        if (count($data) < $page['length']) {
                            $pageObj->update(['offset' => $page['offset'] + count($data), 'is_finished' => 1], ['id' => $page['id']]);
                        } else {
                            $pageObj->update(['offset' => $page['offset'] + count($data)], ['id' => $page['id']]);
                        }

                        unset($insertData);
                        Db::commit();
                        echo "success";
                    } else {
                        throw new Exception('新增失败');
                    }
                } else {
                    if ($akAmazonDailyListRes['message'] == "success") {

                        $pageObj->update(['offset' => $page['offset'] + count($data), 'is_finished' => 1], ['id' => $page['id']]);
                    } else {
                        throw new Exception('接口异常');
                    }
                }
            } catch (\SoapFault $e) {
                Db::rollback();
                dump('SoapFault:'.$e);
            } catch (\Exception $e) {
                Db::rollback();
                dump('Exception:'.$e);
            }
        } else {
            echo "success";exit();
        }
    }
}