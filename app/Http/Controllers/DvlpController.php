<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use \LZCompressor\LZString;

class DvlpController extends Controller
{
    public function index()
    {
        $sign = BpjsConfigBridging::getSignature();

        // return BpjsConfigBridging::getHeader();

        $service_name = 'vclaim-rest-dev';
        $base_url = 'https://apijkn-dev.bpjs-kesehatan.go.id/';
        // {BASE URL}/{Service Name}/Rujukan/RS/{parameter}
        $no_rujukan = '132701010323P000003';
        $url2 = 'https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest-dev/Rujukan/' . $no_rujukan;
        $url = $base_url . $service_name .  "/" . $no_rujukan;

        // $headers = [
        //     'X-cons-id' => $sign['xconsid'],
        //     'X-timestamp' => $sign['xtimestamp'],

        //     'X-signature' => $sign['xsignature'],
        //     'user_key' => $sign['user_key']
        // ];

        // return $headers;
        $response = Http::withHeaders(BpjsConfigBridging::getHeader())->get($url2);
        if (!$response) {
            return response()->json([
                'message' => 'ERRROR'
            ], 500);
        }

        $statusCode = $response->status();
        // $responseBody = json_decode(
        //     $response->getBody(),
        //     true
        // );
        $data = json_decode($response, true);

        $kunci = $sign['xconsid'] . $sign['secret_key'] . $sign['xtimestamp'];
        $nilairespon = $data["response"];
        $hasilakhir = BpjsConfigBridging::decompress(BpjsConfigBridging::stringDecrypt($kunci, $nilairespon));

        $data['result'] = json_decode($hasilakhir);
        return $data;
    }
}
