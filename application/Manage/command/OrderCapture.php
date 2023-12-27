<?php
namespace app\Manage\command;

use app\Manage\model\OrderModel;
use SoapClient;
use SoapFault;
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
        $orders = self::getOrderList();
        file_put_contents( APP_PATH . '/../runtime/log/test.log', PHP_EOL . "[" . date('Y-m-d H:i:s') . "] : " . var_export(json_encode($orders),TRUE), FILE_APPEND);
        foreach ($orders as $item) {
            OrderModel::orderSave($item);
        }
        $output->writeln("success");
    }

    /**
     * @throws SoapFault
     */
    protected function getOrderList($num = 50): array
    {
        $url = "http://nt5e7hf-eb.eccang.com/default/svc-open/web-service-v2";
        $soapClient = new SoapClient($url);
        $params = [
            'paramsJson'    =>  '{"page":1,"pageSize":' . $num . ',"getDetail":1,"getAddress":1,"getCustomOrderType":1,"condition":{"idDesc":1}}',
            'userName'      =>  "NJJ",
            'userPass'      =>  "alex02081888",
            'service'       =>  "getOrderList"
        ];
        $ret = $soapClient->callService($params);
        $retArr = get_object_vars($ret);
        $retJson = $retArr['response'];
        $result = json_decode($retJson, true);
        $data = $result['data'];
        return array_reverse($data);
    }
}