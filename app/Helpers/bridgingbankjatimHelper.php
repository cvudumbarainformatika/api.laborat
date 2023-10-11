<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use LZCompressor\LZString;

class bridgingbankjatimHelper
{

    public static function cretaeqris(string $name, $param)
    {
        $base_url = 'https://jatimva.bankjatim.co.id/MC/Qris/Dynamic';

        $url = $base_url . $service_name . '/' . $param;
        return $url;
    }


    public static function get_url(string $name, $param)
    {

        $url = self::ws_url($name, $param);
        //  $url = self::ws_url_dev($name, $param);


        $sign = self::getSignature($name);
        $kunci = $sign['xconsid'] . $sign['secret_key'] . $sign['xtimestamp'];

        $header = self::getHeader($sign);
        $response = Http::withHeaders($header)->get($url);

        $data = json_decode($response, true);
        // return $data;
        if (!$data) {
            return response()->json([
                'code' => 500,
                'message' => 'ERROR BRIDGING BPJS, cek Internet Atau Bpjs Down'
            ], 500);
        }



        $res['metadata'] = '';
        $res['result'] = 'Tidak ditemukan';

        $res['metadata'] =  $data['metadata'] ??  $data['metaData'];

        // if (!$data["response"]) {
        //     return $res;
        // }
        $nilairespon = $data["response"] ?? false;
        if (!$nilairespon) {
            return $res;
        }
        $hasilakhir = self::decompress(self::stringDecrypt($kunci, $nilairespon));
        $res['result'] = json_decode($hasilakhir);
        if (!$hasilakhir) {
            return response()->json($data);
        }
        return $res;
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

    public static function metaData($code = 200, $msg = 'ok', $value = null)
    {
        $metadata = ['code' => $code, 'message' => $msg];
        $res['metadata'] = $metadata;
        $res['result'] = $value;

        return response()->json($res);
    }
}
