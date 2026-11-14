<?php
namespace app\Manage\command;

use app\Manage\model\AkOpenAPI;
use app\Manage\model\AkOrderListModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class AkOrderListUpdate extends Command
{
    protected function configure()
    {
        $this->setName('AkOrderListUpdate')->setDescription('Here is the AkOrderListUpdate');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $listingObj = new AkOrderListModel();
        $orderList = $listingObj->where(['status' => ['not in', [4, 7]]])->order('id asc')->limit(100)->column('global_order_no');

        Db::startTrans();
        try {
            $params = [
                'offset'                =>  0,
                'length'                =>  100,
                'store_id'              =>  [
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
                'date_type'             =>  'global_payment_time',
                'global_order_nos'      =>  $orderList
            ];
            $akAmazonDailyListRes = AkOpenAPI::makeRequest("/pb/mp/order/v2/list", "POST", $params);
            $data = $akAmazonDailyListRes['data'];
            if (count($data['list']) == 0) {

                Db::commit();
                echo "success";exit();
            }
            if ($akAmazonDailyListRes['code'] == 0 || count($data['list']) > 0) {
                $updateData = [];
                foreach ($data['list'] as $listing) {
                    $item = $listingObj->where(['global_order_no' => $listing['global_order_no']])->find();
                    if ($item['status'] != $listing['status']) {
                        $updateData[] = [
                            "id"                    =>  $item['id'],
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
                if ($updateData) {
                    if ($listingObj->saveAll($updateData)) {

                        unset($data);
                        Db::commit();
                        echo "success";exit();
                    } else {
                        throw new Exception('更新失败');
                    }
                } else {

                    Db::commit();
                    echo "success";exit();
                }
            } else {
                throw new Exception('接口异常');
            }
        } catch (\SoapFault $e) {
            Db::rollback();
            dump('SoapFault:'.$e);
        } catch (\Exception $e) {
            Db::rollback();
            dump('Exception:'.$e);
        }
    }
}