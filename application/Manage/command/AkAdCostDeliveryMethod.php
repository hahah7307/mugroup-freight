<?php
namespace app\Manage\command;

use app\Manage\model\AkAdCostModel;
use Exception;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class AkAdCostDeliveryMethod extends Command
{
    protected function configure()
    {
        $this->setName('AkAdCostDeliveryMethod')->setDescription('Here is the AkAdCostDeliveryMethod');
    }

    /**
     * @throws Exception
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        Db::startTrans();
        try {
            $akAdCostObj = new AkAdCostModel();
            $list = $akAdCostObj->where(['is_finished' => 0])->limit(500)->select();
            foreach ($list as $item) {
                $fbaSum = $akAdCostObj->where(['msku' => $item['msku'], 'reportDateMonth' => $item['reportDateMonth'], 'sid' => $item['sid']])->sum('fbaSaleAmount');
                $fbmSum = $akAdCostObj->where(['msku' => $item['msku'], 'reportDateMonth' => $item['reportDateMonth'], 'sid' => $item['sid']])->sum('fbmSaleAmount');
                if ($fbaSum == 0 && $fbmSum) {
                    $is_fba = 0;
                } elseif ($fbaSum && $fbmSum == 0) {
                    $is_fba = 1;
                } else {
                    if (in_array($item['sid'], [506954,506955,506951,506952,506953])) {
                        $is_fba = 1;
                    } else {
                        $is_fba = $item['fbaSaleAmount'] == 0 ? 0 : 1;
                    }
                }

                $akAdCostObj->update(['is_fba' => $is_fba, 'is_finished' => 1], ['id' => $item['id']]);
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