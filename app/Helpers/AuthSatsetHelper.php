<?php

namespace App\Helpers;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use LZCompressor\LZString;

class AuthSatsetHelper
{

    public static function guzzleToken()
    {
        $url_dev = 'https://api-satusehat-dev.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';
        $url_staging = 'https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';
        $url_prod = 'https://api-satusehat.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';
        $client_id = '8Sy0DMwjAfINN24Wa22u0YcieLLc71bSmGkGqCFsDBcyhG1r';
        $client_secret = 'mj5cQtOjlkhGdK3nOl1YcGyAFx92WTWtALbdPJZIVMfFXDXGCSS6D35HZeWONwFJ';

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];

        $options = [
            'headers' => $headers,
            'form_params' => [
                'client_id' => $client_id,
                'client_secret' => $client_secret
            ]
        ];

        $client = new Client();
        $request = $client->post($url_dev, $options);
        return $request;
    }
    public static function getToken()
    {
        $url_dev = 'https://api-satusehat-dev.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';
        $url_staging = 'https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';
        $url_prod = 'https://api-satusehat.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials';

        $client_id = '8Sy0DMwjAfINN24Wa22u0YcieLLc71bSmGkGqCFsDBcyhG1r';
        $client_secret = 'mj5cQtOjlkhGdK3nOl1YcGyAFx92WTWtALbdPJZIVMfFXDXGCSS6D35HZeWONwFJ';

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];



        $response = Http::withHeaders($headers)
            ->asForm()->post($url_dev, [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
            ]);
        return $response;
    }
}
