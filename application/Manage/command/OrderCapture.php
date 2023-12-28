<?php
namespace app\Manage\command;

use app\Manage\model\OrderModel;
use app\Manage\model\OrderPageModel;
use SoapClient;
use SoapFault;
use think\Config;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;

class OrderCapture extends Command
{
    protected function configure()
    {
        $this->setName('orderCapture')->setDescription('Here is the orderCapture');
    }

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws Exception|SoapFault
     */
    protected function execute(Input $input, Output $output)
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        // 订单查询当前页数
        $data = OrderPageModel::find()->toArray();
        $page = $data['page'];

        $orders = self::getOrderList($page, Config::get('order_page_num'));
        $count = 0;
        foreach ($orders as $key => $item) {
            $count++;
            $isLast = $count == count($orders) ? $key + 1 : 0;
            $isPageUp = $count == Config::get('order_page_num');
            OrderModel::orderSave($isLast, $isPageUp, $item);
        }

        $output->writeln("success");
    }

    /**
     * @throws SoapFault
     */
    protected function getOrderList($page = 1, $num = 50): array
    {
        $url = "http://nt5e7hf-eb.eccang.com/default/svc-open/web-service-v2";
        $soapClient = new SoapClient($url);
        $params = [
            'paramsJson'    =>  '{"page":' . intval($page) . ',"pageSize":' . intval($num) . ',"getDetail":1,"getAddress":1,"getCustomOrderType":1}',
            'userName'      =>  "NJJ",
            'userPass'      =>  "alex02081888",
            'service'       =>  "getOrderList"
        ];
        $ret = $soapClient->callService($params);
        file_put_contents( APP_PATH . '/../runtime/log/test.log', PHP_EOL . "[" . date('Y-m-d H:i:s') . "] : " . var_export($ret,TRUE), FILE_APPEND);
        $retArr = get_object_vars($ret);
        $retJson = $retArr['response'];
        $result = json_decode($retJson, true);
        return $result['data'];
    }
}