<?php

namespace app\Manage\model;

use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsResponse;
use think\Model;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use \Exception;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Utils\Utils;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;


class AliyunSms extends Model
{
    /**
     * 使用AK&SK初始化账号Client
     * @return Dysmsapi Client
     */
    public static function createClient(): Dysmsapi
    {
        \think\Config::load(APP_PATH . 'storage.php');

        // 工程代码泄露可能会导致 AccessKey 泄露，并威胁账号下所有资源的安全性。以下代码示例仅供参考。
        // 建议使用更安全的 STS 方式，更多鉴权访问方式请参见：https://help.aliyun.com/document_detail/311677.html。
        $config = new Config([
            // 必填，请确保代码运行环境设置了环境变量 ALIBABA_CLOUD_ACCESS_KEY_ID。
            "accessKeyId"       =>  \think\Config::get('ALIBABA_CLOUD_ACCESS_KEY_ID'),
            // 必填，请确保代码运行环境设置了环境变量 ALIBABA_CLOUD_ACCESS_KEY_SECRET。
            "accessKeySecret"   =>  \think\Config::get('ALIBABA_CLOUD_ACCESS_KEY_SECRET')
        ]);
        // Endpoint 请参考 https://api.aliyun.com/product/Dysmsapi
        $config->endpoint = "dysmsapi.aliyuncs.com";
        return new Dysmsapi($config);
    }

    /**
     * @param string $phoneNumber
     * @param array $templateParam
     * @return SendSmsResponse
     */
    public static function main(string $phoneNumber, array $templateParam): \AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsResponse
    {
        \think\Config::load(APP_PATH . 'storage.php');

        $args = [
            "phoneNumbers"      =>  $phoneNumber,
            "signName"          =>  \think\Config::get('sign_name'),
            "templateCode"      =>  \think\Config::get('template_code'),
            "templateParam"     =>  json_encode($templateParam)
        ];
        $client = self::createClient();
        $sendSmsRequest = new SendSmsRequest($args);
        try {
            // 复制代码运行请自行打印 API 的返回值
            return $client->sendSmsWithOptions($sendSmsRequest, new RuntimeOptions([]));
        }
        catch (Exception $error) {
            if (!($error instanceof TeaError)) {
                $error = new TeaError([], $error->getMessage(), $error->getCode(), $error);
            }
            // 此处仅做打印展示，请谨慎对待异常处理，在工程项目中切勿直接忽略异常。
            // 错误 message
            var_dump($error->message);
            // 诊断地址
            var_dump($error->data["Recommend"]);
            Utils::assertAsString($error->message);
        }
    }
}
