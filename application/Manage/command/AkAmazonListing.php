<?php
namespace app\Manage\command;

use app\Manage\model\AkAmazonListingModel;
use app\Manage\model\AkAmazonListingPageModel;
use app\Manage\model\AkOpenAPI;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class AkAmazonListing extends Command
{
    protected function configure()
    {
        $this->setName('AkAmazonListing')->setDescription('Here is the AkAmazonListing');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        $pageObj = new AkAmazonListingPageModel();
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
                    'sid'           =>  $page['sid']
                ];
                $akAmazonListingRes = AkOpenAPI::makeRequest("/erp/sc/data/mws/listing", "POST", $params);
                $data = $akAmazonListingRes['data'];
                $listingObj = new AkAmazonListingModel();
                if (!empty($data)) {
                    $insertData = [];
                    foreach ($data as $listing) {
                        $listing['last_star'] = !empty($listing['last_star']) ? $listing['last_star'] : NULL;
                        $listing['shipping'] = !empty($listing['shipping']) ? $listing['shipping'] : NULL;
                        $listing['open_date'] = !empty($listing['open_date']) ? date('Y-m-d H:i:s', strtotime($listing['open_date'])) : NULL;
                        $listing['open_date_display'] = !empty($listing['open_date_display']) ? date('Y-m-d H:i:s', strtotime($listing['open_date_display'])) : NULL;
                        $listing['pair_update_time'] = !empty($listing['pair_update_time']) ? $listing['pair_update_time'] : NULL;
                        $listing['on_sale_time'] = !empty($listing['on_sale_time']) ? $listing['on_sale_time'] : NULL;
                        $listing['first_order_time'] = !empty($listing['first_order_time']) ? $listing['first_order_time'] : NULL;
                        $listing['landed_price'] = !empty($listing['landed_price']) ? $listing['landed_price'] : 0;
                        $listing['listing_price'] = !empty($listing['listing_price']) ? $listing['listing_price'] : 0;
                        $listing['dimension_info'] = isset($listing['dimension_info']) ? json_encode($listing['dimension_info']) : json_encode([]);
                        $listing['small_rank'] = isset($listing['small_rank']) ? json_encode($listing['small_rank']) : json_encode([]);
                        $listing['seller_category_new'] = isset($listing['seller_category_new']) ? json_encode($listing['seller_category_new']) : json_encode([]);
                        $listing['principal_info'] = isset($listing['principal_info']) ? json_encode($listing['principal_info']) : json_encode([]);
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
                    throw new Exception('接口异常');
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