<?php

namespace App\Helpers\Eklaim;

use Exception;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Eklaim
{
    public static function inacbg_encrypt($data, $key)
    {
        $key = hex2bin($key);
        if ($key === false || mb_strlen($key, '8bit') !== 32) {
            throw new Exception('Needs a 256-bit key!');
        }

        $ivSize = openssl_cipher_iv_length('aes-256-cbc');
        $iv = openssl_random_pseudo_bytes($ivSize);
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new RuntimeException('Failed to encrypt E-Klaim request.');
        }

        $signature = mb_substr(
            hash_hmac('sha256', $encrypted, $key, true),
            0,
            10,
            '8bit'
        );

        return chunk_split(base64_encode($signature.$iv.$encrypted));
    }

    public static function inacbg_decrypt($str, $strkey)
    {
        $key = hex2bin($strkey);
        if ($key === false || mb_strlen($key, '8bit') !== 32) {
            throw new Exception('Needs a 256-bit key!');
        }

        $ivSize = openssl_cipher_iv_length('aes-256-cbc');
        $decoded = base64_decode($str, true);
        if ($decoded === false || mb_strlen($decoded, '8bit') < 10 + $ivSize) {
            return 'SIGNATURE_NOT_MATCH';
        }

        $signature = mb_substr($decoded, 0, 10, '8bit');
        $iv = mb_substr($decoded, 10, $ivSize, '8bit');
        $encrypted = mb_substr($decoded, $ivSize + 10, null, '8bit');
        $calculatedSignature = mb_substr(
            hash_hmac('sha256', $encrypted, $key, true),
            0,
            10,
            '8bit'
        );

        if (!self::inacbg_compare($signature, $calculatedSignature)) {
            return 'SIGNATURE_NOT_MATCH';
        }

        return openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }

    public static function inacbg_compare($a, $b)
    {
        return is_string($a)
            && is_string($b)
            && hash_equals($a, $b);
    }

    public static function curl_func($ws_query)
    {
        $config = DB::table('klaim_ws_config')
            ->select('ws_keys', 'ip_server')
            ->first();

        if (!$config || !$config->ws_keys || !$config->ip_server) {
            throw new RuntimeException('Konfigurasi web service E-Klaim tidak ditemukan.');
        }

        $key = $config->ws_keys;
        $url = 'http://'.$config->ip_server.'/E-Klaim/ws.php';
        $jsonRequest = json_encode($ws_query, JSON_THROW_ON_ERROR);
        $payload = self::inacbg_encrypt($jsonRequest, $key);

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
        ]);
        $response = curl_exec($curl);
        if ($response === false) {
            $message = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException('Gagal menghubungi E-Klaim: '.$message);
        }
        curl_close($curl);

        $lines = preg_split('/\r\n|\r|\n/', trim($response));
        $encryptedResponse = count($lines) > 1
            ? implode('', array_slice($lines, 1, -1))
            : $response;
        $decrypted = self::inacbg_decrypt($encryptedResponse, $key);

        return json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
    }
}
