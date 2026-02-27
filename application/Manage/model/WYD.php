<?php

namespace app\Manage\model;

use think\exception\DbException;
use think\Model;

class WYD extends Model
{
    /**
     * AES密码
     */
    public static $AES_PWD = 'b8w5BEsBlz7F/iCdpph4iQ==';

    /**
     * 使用AES算法对内容进行加密
     *
     * @param string $content 需要加密的内容
     * @param string $keyString 加密使用的密钥（base64编码）
     * @return string 返回加密后的字符串（base64编码）
     */
    static public function aesEncrypt(string $content, string $keyString): string
    {
        // 将密钥从base64编码解码为二进制
        $key = base64_decode($keyString);
        // var_dump(bin2hex($key));exit();
        // 使用AES-256-CBC模式对内容进行加密，返回加密后的二进制数据
        $encrypted = openssl_encrypt($content, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        // 将加密后的二进制数据编码为base64字符串，方便传输或存储
        return base64_encode($encrypted);
    }

    static public function aesDecrypt($body, $keyString)
    {
        $key           = base64_decode($keyString);
        $encryptedData = base64_decode($body);
        return openssl_decrypt($encryptedData, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }
}
