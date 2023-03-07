<?php

namespace App\Http\Controllers\Api\Bridgingvclaim;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\TransaksiLaborat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BpjsController extends Controller
{
    public function signature()
    {
        $data = "testtesttest";
        $secretKey = "secretkey";
        // Computes the timestamp
        date_default_timezone_set('UTC');
        $tStamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        // Computes the signature by hashing the salt with the secret key as the key
        $signature = hash_hmac('sha256', $data . "&" . $tStamp, $secretKey, true);

        // base64 encode�
        $encodedSignature = base64_encode($signature);

        // urlencode�
        // $encodedSignature = urlencode($encodedSignature);

        // echo "X-cons-id: " . $data . " ";
        // echo "X-timestamp:" . $tStamp . " ";
        // echo "X-signature: " . $encodedSignature;


        // $headers = [
        //     'X-cons-id' => $data,
        //     'X-timestamp' => $tStamp,
        //     'X-signature' => $signature,
        //     // 'Accept' => 'application/json'
        // ];

        // $response = Http::withHeaders($headers)->get($apiURL);
        // if (!$response) {
        //     return response()->json([
        //         'message' => 'Harap Ulangi... LIS ERROR'
        //     ], 500);
        // }

        // $statusCode = $response->status();
        // $responseBody = json_decode($response->getBody(), true);
    }

    // public function kirim_ke_lis()
    // {
    //     $xid = "4444";
    //     $secret_key = 'l15Test';
    //     date_default_timezone_set('UTC');
    //     $xtimestamp = strval(time() - strtotime('1970-01-01 00:00:00'));
    //     $sign = hash_hmac('sha256', $xid . "&" . $xtimestamp, $secret_key, true);
    //     $xsignature = base64_encode($sign);

    //     $apiURL = 'http://172.16.24.2:83/prolims/api/lis/postOrder';


    //     $headers = [
    //         'X-id' => $xid,
    //         'X-timestamp' => $xtimestamp,
    //         'X-signature' => $xsignature,
    //         // 'Accept' => 'application/json'
    //     ];

    //     $response = Http::withHeaders($headers)->post($apiURL, $request->all());
    //     if (!$response) {
    //         return response()->json([
    //             'message' => 'Harap Ulangi... LIS ERROR'
    //         ], 500);
    //     }

    //     $statusCode = $response->status();
    //     $responseBody = json_decode($response->getBody(), true);

    //     TransaksiLaborat::where('rs2', $request->ONO)->update(['rs18' => "1"]);

    //     return $responseBody;
    // }
}
