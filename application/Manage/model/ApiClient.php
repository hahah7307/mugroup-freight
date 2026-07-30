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
        if (!empty($result['code']) && $result['code'] != "200") {
            return ['code' => 0, 'msg' => "error code: " . $result['code'] . "(" . $result['message'] . ")"];
        }

        if (!empty($result['ask']) && $result['ask'] != "Success") {
            return ['code' => 0, 'msg' => "error code: " . $result['code'] . "(" . $result['message'] . ")"];
        }


        return ['code' => 1, 'data' => $result['data']];
    }

    /**
     * @throws \SoapFault
     * @throws \Exception
     */
    static public function LcWarehouseApi($tag, $jsonData): array
    {
        $url = Config::get('lc_api_uri');
        try {
            $soapClient = new SoapClient($url);
            $params = [
                'paramsJson'    =>  $jsonData,
                'appToken'      =>  Config::get('lc_app_token'),
                'appKey'        =>  Config::get('lc_app_key'),
                'language'      =>  'zh_CN',
                'service'       =>  $tag
            ];
            $ret = $soapClient->callService($params);
            $retArr = get_object_vars($ret);
            $retJson = $retArr['response'];
            $result = json_decode($retJson, true);

            return ['code' => 1, 'data' => $result['data']];
        } catch (\SoapFault $e) {

            return ['code' => 0, 'msg' => $e->getMessage()];
        }
    }

    static public function LeWarehouseApi($url, $method = 'POST', $params = []): array
    {
        $params = array_merge(['accessKey' => Config::get('le_access_key'), 'timestamp' => self::getMillisecond()], $params);
        $data = self::httpCurl($url, $method, $params);
        $result = json_decode($data, true);
        if ($result['code'] != 200) {
            return ['code' => 0, 'msg' => $result['message']];
        }

        return ['code' => 1, 'data' => $result['data']];
    }

    static public function WydWarehouseApi($url, $method = 'POST', $params = []): array
    {
        $url = Config::get('wyd_api_uri') . $url;
        $initParams = [
            "uniqueNo"          =>  "37f55f1f-c0ae-45d1-a8e1-3f00f8f29be0",
            "body"              =>  WYD::aesEncrypt(\GuzzleHttp\json_encode($params), Config::get('wyd_app_secret')),
            "timezoneOffset"    =>  8,
            "source"            =>  "NBTLZ01",
            "appId"             =>  Config::get('wyd_app_id'),
            "timestamp"         =>  round(microtime(true) * 1000)
        ];
        $data = self::wydHttpCurl($url, $method, $initParams);
        $result = json_decode($data, true);
        if ($result['code'] != 200) {
            return ['code' => 0, 'msg' => $result['message']];
        } else {
            $data = WYD::aesDecrypt($result['data'], Config::get('wyd_app_secret'));
            return ['code' => 1, 'data' => $data];
        }
    }

    static public function edaWarehouseApi($url, $method = 'POST', $params = []): array
    {
        $url = Config::get('eda_api_uri') . $url;
        $header = [
            'Content-Type: application/json',
            'Authorization: ' . Config::get('eda_token'),
            'timestamp: ' . time(),
            'appKey: ' . Config::get('eda_app_key'),
            'sign: ' . md5(json_encode($params) . '.' . Config::get('eda_app_secret'))
        ];
        $data = self::edaHttpCurl($url, $header, $method, $params);
        return ['code' => 1, 'data' => json_decode($data, true)];
    }

    static public function edaHttpCurl($url, $header, $method = 'POST', $params = false){
        $ch = curl_init();
        // 关闭SSL验证
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if( $method == 'POST' ) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $response = curl_exec( $ch );
        if ($response === FALSE) {
            return false;
        }
        curl_close( $ch );

        return $response;
    }

    static public function httpCurl($url, $method = 'POST', $params = false){
        $ch = curl_init();
        // 关闭SSL验证
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);

        // 设置请求头
        $header = [
            'Content-Type: application/json',
            'accessKey: ' . $params['accessKey'],
            'timestamp: ' . $params['timestamp'],
            'sign: ' . self::params2sign($params, Config::get('le_secret_key'))
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        unset($params['accessKey']);
        unset($params['timestamp']);
        if( $method == 'POST' ) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $response = curl_exec( $ch );
        if ($response === FALSE) {
            return false;
        }
        curl_close( $ch );

        return $response;
    }

    static public function wydHttpCurl($url, $method = 'POST', $params = false){
        $ch = curl_init();
        // 关闭SSL验证
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);

        // 设置请求头
        $header = [
            'Content-Type: application/json',
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if( $method == 'POST' ) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $response = curl_exec( $ch );
        if ($response === FALSE) {
            return false;
        }
        curl_close( $ch );

        return $response;
    }

    static public function params2sign($data, $secretKey): string
    {
        ksort($data);
        $longString = "";
        foreach ($data as $key => $value) {
            $longString .= "&" . $key . "=" . $value;
        }
        $longString = substr($longString, 1, strlen($longString));

        return hash('sha256', $longString . $secretKey);
    }

    static public function getMillisecond(){
        list($mse, $sec) = explode(' ', microtime());
        $onetime =  (float)sprintf('%.0f', (floatval($mse) + floatval($sec)) * 1000);

        return substr($onetime,0,13);
    }
}
