<?php

namespace App\Http\Controllers;

use App\Helpers\BridgingbpjsHelper;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use \LZCompressor\LZString;

class DvlpController extends Controller
{
    public function index()
    {
        $sign = BpjsConfigBridging::getSignature();

        // return BridgingbpjsHelper::get_url('vclaim');

        $service_name = 'vclaim-rest-dev';
        $base_url = 'https://apijkn-dev.bpjs-kesehatan.go.id/';
        // {BASE URL}/{Service Name}/Rujukan/RS/{parameter}
        $no_rujukan = '132701010323P000003';
        // $url2 = 'https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest-dev/Rujukan/' . $no_rujukan;
        // $url = $base_url . $service_name .  "/" . $no_rujukan;

        // $headers = [
        //     'X-cons-id' => $sign['xconsid'],
        //     'X-timestamp' => $sign['xtimestamp'],

        //     'X-signature' => $sign['xsignature'],
        //     'user_key' => $sign['user_key']
        // ];

        // $url = BridgingbpjsHelper::get_url('vclaim') . 'Rujukan/' . $no_rujukan;
        // $url = BridgingbpjsHelper::get_url('vclaim') . 'referensi/poli/geriatri';


        $url =  'https://apijkn-dev.bpjs-kesehatan.go.id/antreanrs_dev/' . 'ref/poli';
        // $url =  'https://apijkn-dev.bpjs-kesehatan.go.id/antreanrs_dev/' . 'antrean/getlisttask';
        // $url =  'https://apijkn-dev.bpjs-kesehatan.go.id/antreanrs_dev/' . 'ref/dokter';

        // return $headers;
        $response = Http::withHeaders(BridgingbpjsHelper::getHeader())->get($url);
        if (!$response) {
            return response()->json([
                'message' => 'ERRROR'
            ], 500);
        }

        // $statusCode = $response->status();
        // // $responseBody = json_decode(
        // //     $response->getBody(),
        // //     true
        // // );
        $data = json_decode($response, true);

        $kunci = $sign['xconsid'] . $sign['secret_key'] . $sign['xtimestamp'];
        $nilairespon = $data["response"];
        $hasilakhir = BridgingbpjsHelper::decompress(BridgingbpjsHelper::stringDecrypt($kunci, $nilairespon));

        $res['metadata'] = $data['metadata'];
        $res['result'] = json_decode($hasilakhir);
        return $res;
    }

    public function antrian()
    {
        $reqLog = (new Client())->post('http://192.168.160.100:2000/api/api' . '/get_list_antrian_tanggal', [
            'form_params' => [
                'tanggal' => date('Y-m-d')
            ],
            'http_errors' => false
        ]);
        $resLog = json_decode($reqLog->getBody()->getContents(), true);

        return $resLog;
    }
}
