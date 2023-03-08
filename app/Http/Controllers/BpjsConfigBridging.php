<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use LZCompressor\LZString;

class BpjsConfigBridging extends Controller
{
    public static function getSignature()
    {
        $user_key = "fbad382d69383c78969f889077053ebb";

        $data = "31014";
        $secretKey = "3sY5CB0658";
        // Computes the timestamp
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        // Computes the signature by hashing the salt with the secret key as the key
        $signature = hash_hmac('sha256', $data . "&" . $tStamp, $secretKey, true);

        // base64 encode�
        $encodedSignature = base64_encode($signature);

        $data = array(
            'xconsid' => $data,
            'xtimestamp' => $tStamp,
            'xsignature' => $encodedSignature,
            'user_key' => $user_key,
            'secret_key' => $secretKey
        );

        return $data;
    }

    public static function getHeader()
    {
        $data = self::getSignature();
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-cons-id' => $data['xconsid'],
            'X-timestamp' => $data['xtimestamp'],
            'X-signature' => $data['xsignature'],
            'user_key' => $data['user_key'],
        ];
    }

    public static function stringDecrypt($key, $string)
    {
        // $key = $sign['xconsid'] . $sign['secret_key'] . $time;
        $encrypt_method = 'AES-256-CBC';
        $key_hash = hex2bin(hash('sha256', $key));
        $iv = substr(hex2bin(hash('sha256', $key)), 0, 16);
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key_hash, OPENSSL_RAW_DATA, $iv);
        return $output;
    }

    public static function decompress($string)
    {
        return LZString::decompressFromEncodedURIComponent($string);
    }
}
