<?php

namespace app\Manage\model;

use Ak\OpenAPI\Exception\GenerateAccessTokenException;
use Ak\OpenAPI\Exception\InvalidAccessTokenException;
use Ak\OpenAPI\Exception\InvalidResponseException;
use Ak\OpenAPI\Exception\RequestException;
use Ak\OpenAPI\Exception\RequiredParamsEmptyException;
use Ak\OpenAPI\Services\OpenAPIRequestService;
use think\Config;
use think\Model;

class AkOpenAPI extends Model
{
    public function __construct($data = [])
    {
        // 加载自定义配置
        Config::load(APP_PATH . 'storage.php');

        parent::__construct($data);
    }

    /**
     * @throws RequiredParamsEmptyException
     * @throws InvalidAccessTokenException
     * @throws InvalidResponseException
     * @throws RequestException
     */
    static public function makeRequest($url, $method, $params = []): array
    {
        $client = new OpenAPIRequestService(Config::get("ak_openapi_uri"), Config::get("ak_app_id"), Config::get("ak_access_token"));
        /**
         * 发起请求前需要先生成AccessToken或手动设置AccessToken，否则会抛出 InvalidAccessTokenException
         * AccessToken有时效性，可以自行加入缓存，并判断是否已过期，方便续约或重新生成
         */
        $accessTokenDto = $client->generateAccessToken();

        /**
         * 获取AccessToken
         */
//        $accessTokenDto->getAccessToken();

        /**
         * 获取RefreshToken（用于刷新AccessToken），请自行保存好
         */
//        $accessTokenDto->getRefreshToken();

        /**
         * 获取过期时间戳，请自行保存好，用于判断AccessToken是否已过期
         */
//        $accessTokenDto->getExpireAt();

        /**
         * 刷新AccessToken，AccessToken到期前需续约，这里请自行判断AccessToken的有效期
         */
//        $client->refreshToken($accessTokenDto->getRefreshToken());

        /**
         * 手动设置AccessToken
         */
//        $accessToken = 'get_access_token_from_cache';
//        $client->setAccessToken($accessToken);

        if ($method == 'GET') {
            /**
             * GET 请求示例
             * $res 会是一个数组，接口文档返回结果json_decode()后的数组结果
             */
            return $client->makeRequest($url, 'GET');
        } elseif ($method == 'POST') {
            /**
             * POST 请求示例
             */
            return $client->makeRequest($url, 'POST', $params);
        } else {
            return [];
        }
    }
}
