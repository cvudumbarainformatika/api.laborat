<?php

namespace App\Helpers;

use LZCompressor\LZString;

class BridgingbpjsHelper
{


    public static function get_url(string $name)
    {
        $base_url = 'https://apijkn-dev.bpjs-kesehatan.go.id/';
        $service_name = 'vclaim-rest-dev';
        if ($name === 'antrean') {
            $service_name = 'antreanrs_dev';
        } else if ($name === 'apotek') {
            $service_name = 'apotek-rest-dev';
        } else if ($name === 'pcare') {
            $service_name = 'apotek-rest-dev';
        } else {
            $service_name = 'vclaim-rest-dev';
        }

        return $base_url . $service_name . '/';
    }


    public static function getSignature()
    {
        // BPJS_ANTREAN_CONS_ID=31014
        // BPJS_ANTREAN_SECRET=3sY5CB0658
        // BPJS_ANTREAN_USER_KEY=140dbebe0248aa4ce64557a8ffbdb0e9
        // BPJS_ANTREAN_USER_KEY_DEV=f5abd04a8fadc1061e8853715662c3e8

        $BPJS_ANTREAN_SECRET = '3sY5CB0658';
        $BPJS_ANTREAN_USER_KEY = 'f5abd04a8fadc1061e8853715662c3e8';


        $VCLAIM_DEV_USER_KEY_DEV = "fbad382d69383c78969f889077053ebb";
        $VCLAIM_DEV_USER_KEY = 'belum_ada';

        $cons = "31014";
        $secretKey = "3sY5CB0658";
        // Computes the timestamp
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        // Computes the signature by hashing the salt with the secret key as the key
        $signature = hash_hmac('sha256', $cons . "&" . $tStamp, $secretKey, true);

        // base64 encode�
        $encodedSignature = base64_encode($signature);

        $data = array(
            'xconsid' => $cons,
            'xtimestamp' => $tStamp,
            'xsignature' => $encodedSignature,
            'user_key' => $VCLAIM_DEV_USER_KEY_DEV, // ini untuk vclaim
            // 'user_key' => $BPJS_ANTREAN_USER_KEY, // ini untuk antrean
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


        // return [
        //     'Accept' => 'application/json',
        //     'Content-Type' => 'application/json',
        //     'x-cons-id' => $data['xconsid'],
        //     'x-timestamp' => $data['xtimestamp'],
        //     'x-signature' => $data['xsignature'],
        //     'user_key' => $data['user_key'],
        // ];
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
