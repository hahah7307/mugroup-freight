<?php
namespace app\Manage\command;

use app\Manage\model\AkOpenAPI;
use app\Manage\model\AkOrderListModel;
use app\Manage\model\AkOrderListPageModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class AkOrderList extends Command
{
    protected function configure()
    {
        $this->setName('AkOrderList')->setDescription('Here is the AkOrderList');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $pageObj = new AkOrderListPageModel();
        $page = $pageObj->find(1);
        if ($page) {
            Db::startTrans();
            try {
                $params = [
                    'offset'        =>  $page['offset'],
                    'length'        =>  $page['length'],
                    'store_id'      =>  [
                        "110683227834042880",
                        "110683251380869632",
                        "110683251510258688",
                        "110683251625679872",
                        "110683251737728512",
                        "110683252072926720",
                        "110683252341814272",
                        "110683252433235968",
                        "110683252233838080"
                    ],
                    'date_type'     =>  'global_payment_time',
                    'start_time'    =>  strtotime($page['date']),
                    'end_time'      =>  strtotime('+1 day', strtotime($page['date'])),
                ];
                $akAmazonDailyListRes = AkOpenAPI::makeRequest("/pb/mp/order/v2/list", "POST", $params);
                $data = $akAmazonDailyListRes['data'];
                if (count($data['list']) == 0) {

                    Db::commit();
                    echo "success";exit();
                }
                $listingObj = new AkOrderListModel();
                if ($akAmazonDailyListRes['code'] == 0 || count($data['list']) > 0) {
                    $insertData = [];
                    foreach ($data['list'] as $listing) {
                        $item = $listingObj->where(['global_order_no' => $listing['global_order_no']])->find();
                        if (empty($item)) {
                            $insertData[] = [
                                "global_order_no"       =>  $listing['global_order_no'],
                                "store_name"            =>  $listing['store_name'],
                                "platform_order_no"     =>  $listing['item_info'][0]['platform_order_no'],
                                "status"                =>  $listing['status'],
                                "global_payment_time"   =>  date('Y-m-d H:i:s', $listing['global_payment_time']),
                                "amount_currency"       =>  $listing['amount_currency'],
                                "unit_price_amount"     =>  $listing['item_info'][0]['unit_price_amount'],
                                "item_price_amount"     =>  $listing['item_info'][0]['item_price_amount'],
                                "quantity"              =>  $listing['item_info'][0]['quantity'],
                                "item_info"             =>  json_encode($listing['item_info']),
                                "platform_info"         =>  json_encode($listing['platform_info']),
                                "created_time"          =>  date('Y-m-d H:i:s')
                            ];
                        }
                    }
                    if ($insertData) {
                        if ($listingObj->insertAll($insertData)) {
                            if (count($data['list']) < $page['length']) {
                                if ($page['date'] == date('Ymd')) {
                                    $pageObj->update(['offset' => $page['offset'] + count($data['list'])], ['id' => $page['id']]);
                                } else {
                                    $pageObj->update(['offset' => $page['offset'], 'date' => date('Ymd', strtotime('+1 day', strtotime($page['date'])))], ['id' => $page['id']]);
                                }
                            } else {
                                $pageObj->update(['offset' => $page['offset'] + count($data['list'])], ['id' => $page['id']]);
                            }

                            unset($data);
                            Db::commit();
                            echo "success";exit();
                        } else {
                            throw new Exception('新增失败');
                        }
                    } else {
                        $pageObj->update(['offset' => $page['offset'] + count($data['list'])], ['id' => $page['id']]);

                        Db::commit();
                        echo "success";exit();
                    }
                } else {
                    if ($akAmazonDailyListRes['message'] == "success") {
                        if ($page['date'] == date('Ymd')) {
                            $pageObj->update(['offset' => $page['offset'] + count($data['list'])], ['id' => $page['id']]);
                        } else {
                            $pageObj->update(['offset' => $page['offset'], 'date' => date('Ymd', strtotime('+1 day', strtotime($page['date'])))], ['id' => $page['id']]);
                        }
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