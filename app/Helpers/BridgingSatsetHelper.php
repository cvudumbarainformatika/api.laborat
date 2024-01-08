<?php

namespace App\Helpers;

use App\Models\Satset\Satset;
use App\Models\Satset\SatsetErrorRespon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use LZCompressor\LZString;

class BridgingSatsetHelper
{

    public static function base_url()
    {
        $url_dev = 'https://api-satusehat-dev.dto.kemkes.go.id/fhir-r4/v1';
        $url_staging = 'https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1';
        $url_prod = 'https://api-satusehat.kemkes.go.id/fhir-r4/v1';
        $client_id = '8Sy0DMwjAfINN24Wa22u0YcieLLc71bSmGkGqCFsDBcyhG1r';
        $client_secret = 'mj5cQtOjlkhGdK3nOl1YcGyAFx92WTWtALbdPJZIVMfFXDXGCSS6D35HZeWONwFJ';


        return $url_prod;
    }

    public static function get_data($token, $params)
    {
        $url = self::base_url() . $params;
        $response = Http::withToken($token)->get($url);

        return $response;
    }

    public static function post_data($token, $params, $form)
    {
        $url = self::base_url() . $params;
        $response = Http::withToken($token)->post($url, $form);
        $data = json_decode($response, true);

        // JIKA ERROR
        $error = $data['resourceType'] === 'OperationOutcome';
        if ($error) {
            $err = [
                'method' => 'POST',
                'url' => $params,
                'response' => $data
            ];
            $resp = SatsetErrorRespon::create($err);

            $send = [
                'message' => 'failed',
                'data' => $resp
            ];
            return $send;
        }

        // JIKA SUCCESS
        $success = [
            'method' => 'POST',
            'url' => $params,
            'response' => $data,

        ];
        $resp = Satset::firstOrCreate([
            'resource' => $data['resourceType'],
            'uuid' => $data['id']
        ], $success);
        $send = [
            'message' => 'success',
            'data' => $resp
        ];
        return $send;
    }

    public static function put_data($token, $params, $form)
    {
        $url = self::base_url() . $params;
        $response = Http::withToken($token)->put($url, $form);
        $data = json_decode($response, true);

        // JIKA ERROR
        $error = $data['resourceType'] === 'OperationOutcome';
        if ($error) {
            $err = [
                'method' => 'PUT',
                'url' => $params,
                'response' => $data
            ];
            $resp = SatsetErrorRespon::create($err);

            $send = [
                'message' => 'failed',
                'data' => $resp
            ];
            return $send;
        }

        // JIKA SUCCESS
        $success = [
            'method' => 'PUT',
            'url' => $params,
            'response' => $data,

        ];
        $resp = Satset::updateOrCreate([
            'resource' => $data['resourceType'],
            'uuid' => $data['id']
        ], $success);
        $send = [
            'message' => 'success',
            'data' => $resp
        ];
        return $send;
    }
}
