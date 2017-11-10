<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-27
 * Time: 22:26
 */

namespace Qe\Core;


class Crypt
{
    /**
     * 字符串加密
     *
     * @param string $str 需要加密的字符串
     * @param string $key 密钥
     * @return string 加密后的结果
     */
    public static function encrypt($str, $key, $iv = '')
    {
        $size = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_CBC);
        $iv = substr(md5($iv ? $iv : $key), -$size);
        $pad = $size - (strlen($str) % $size);
        $str .= str_repeat(chr($pad), $pad);
        @$data = mcrypt_cbc(MCRYPT_DES, $key, $str, MCRYPT_ENCRYPT, $iv);
        return base64_encode($data);
    }

    /**
     * 解密字符串
     *
     * @param string $str 解密的字符串
     * @param string $key 密钥
     * @return string 解密后的结果
     */
    public static function decrypt($str, $key, $iv = '')
    {
        $size = mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_CBC);
        $iv = substr(md5($iv ? $iv : $key), -$size);
        $str = base64_decode($str);
        @$str = mcrypt_cbc(MCRYPT_DES, $key, $str, MCRYPT_DECRYPT, $iv);
        $pad = ord($str{strlen($str) - 1});
        if ($pad > strlen($str)) return false;
        if (strspn($str, chr($pad), strlen($str) - $pad) != $pad) return false;
        return substr($str, 0, -1 * $pad);
    }

}