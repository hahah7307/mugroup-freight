<?php

namespace app\Manage\model;

use SoapClient;
use think\Config;
use think\Model;

class ApiClient extends Model
{
    public function __construct($data = [])
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        parent::__construct($data);
    }

    /**
     * @throws \SoapFault
     */
    static public function EcWarehouseApi($url, $tag, $jsonData): array
    {
        header("content-type:text/html;charset=utf-8");
        $soapClient = new SoapClient($url);
        $params = [
            'paramsJson'    =>  $jsonData,
            'userName'      =>  Config::get("ec_warehouse_username"),
            'userPass'      =>  Config::get("ec_warehouse_userpass"),
            'service'       =>  $tag
        ];
        $ret = $soapClient->callService($params);
        $retArr = get_object_vars($ret);
        $retJson = $retArr['response'];
        $result = json_decode($retJson, true);
        if ($result['code'] != "200") {
            return ['code' => 0, 'msg' => "error code: " . $result['code'] . "(" . $result['message'] . ")"];
        }

        return ['code' => 1, 'data' => $result['data']];
    }

    static public function LeWarehouseApi()
    {

    }

    static public function LcWarehouseApi()
    {

    }
}
