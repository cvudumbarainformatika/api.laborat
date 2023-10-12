<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use LZCompressor\LZString;

class bridgingbankjatimHelper
{

    public static function cretaeqris(string $method, $myvars)
    {
        $url = 'https://jatimva.bankjatim.co.id/MC/Qris/Dynamic';

        $session = curl_init($url);
        $arrheader =  array(
            //'Accept: application/json',
            'Content-Type: application/json',
        );

        curl_setopt($session, CURLOPT_URL, $url);
        curl_setopt($session, CURLOPT_HTTPHEADER, $arrheader);
        curl_setopt($session, CURLOPT_VERBOSE, true);

        if ($method == 'POST') {
            curl_setopt($session, CURLOPT_POST, true);
            curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
        }

        if ($method == 'PUT') {
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
        }

        if ($method == 'GET') {
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
        }
        if ($method == 'DELETE') {
            curl_setopt($session, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($session, CURLOPT_POSTFIELDS, $myvars);
        }

        curl_setopt($session, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($session);
        return $response;
    }
}
