<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\Kunjungan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class AutogenController extends Controller
{

    public function index()
    {
        $tableName = 'accounts';
        $data = Schema::getColumnListing($tableName);

        echo '<br>';
        echo '====================================== RESOURCE ============================';
        echo '<br>';
        foreach ($data as $key) {
            echo '\'' . $key . '\' => $this->' . $key . ',<br>';
        }
        echo '<br>';
        echo '====================================== INI UNTUK request->only ============================';
        echo '<br>';
        foreach ($data as $key) {
            echo '\'' . $key . '\',';
        }
        echo '<br>';
        echo '====================================== INI UNTUK QUASAR ============================';
        echo '<br>';
        foreach ($data as $key) {
            echo $key . ': "", <br>';
        }
        echo '<br>';
    }

    public function coba()
    {
        // echo DIRECTORY_SEPARATOR;
        // $upDir = 'uploads' . DIRECTORY_SEPARATOR . Carbon::now()->toDateString() . DIRECTORY_SEPARATOR;
        // Storage::makeDirectory($upDir);
        // echo $upDir;
<<<<<<< HEAD
        // echo hash_hmac('sha256', '4444&1663225969','lisTest');
        $xid = "4444";
        $secret_key = 'l15Test';
        date_default_timezone_set('UTC');
        $xtimestamp = strtotime('2022-09-16 14:12:49');
        $sign = hash_hmac('sha256', $xid . "&" . $xtimestamp, $secret_key, true);
        $xsignature = base64_encode($sign);
        echo '<pre>';
        echo $xtimestamp;
        echo '</pre>';
        echo $xsignature;
        echo '</pre>';

    }

    public function coba_api()
    {
        $xid = "4444";
        $secret_key = 'l15Test';
        date_default_timezone_set('UTC');
        // $xtimestamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $xtimestamp = strtotime('2022-09-16 14:12:49');
        $sign = hash_hmac('sha256', $xid . "&" . $xtimestamp, $secret_key, true);
        $xsignature = base64_encode($sign);
        // echo $xsignature;

        $apiURL = 'http://45.77.35.181:83/prolims/api/lis/postOrder';
        $postInput = [
            "ADDRESS"=> "JL BANTARAN RT5/10 NO.07 SUMBERKEDAWUNG LECES - KOTA PROBOLINGGO",
            "BOD"=>"19981127",
            "CLASS"=>"-",
            "CLASS_NAME"=>"-",
            "COMPANY"=>"-",
            "COMPANY_NAME"=>"RSUD MOCH SALEH",
            "DATE_ORDER"=>"20220916141249",
            "DIAGNOSA"=>"-",
            "DOCTOR"=>"17",
            "DOCTOR_NAME"=>"Abdul Muis, dr. Sp.THT",
            "GLOBAL_COMMENT"=>"-",
            "IDENTITY_N"=>"-",
            "IS_CITO"=>"-",
            "KODE_PRODUCT"=>"LAB183",
            "ONO"=>"220915/37334L",
            "PATIENT_NAME"=>"RAHMAD ARDIANSYAH",
            "EMAIL"=>"aabb@aaa.com",
            "PATIENT_NO"=>"120038",
            "ROOM"=>"POL014",
            "ROOM_NAME"=>"IRD",
            "SEX"=>"1",
            "STATUS"=>"N",
            "TYPE_PATIENT"=>"-"
        ];

        $headers = [
            'X-id' => $xid,
            'X-timestamp' => $xtimestamp,
            'X-signature' => $xsignature,
        ];

        $response = Http::withHeaders($headers)->post($apiURL, $postInput);

        $statusCode = $response->status();
        $responseBody = json_decode($response->getBody(), true);

        // dd($responseBody);
        return response()->json($responseBody);
    }

    public function getDetOrderList()
    {
        $xid = "4444";
        $secret_key = 'l15Test';
        date_default_timezone_set('UTC');
        // $xtimestamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $xtimestamp = strtotime('2022-09-16 14:12:49');
        $sign = hash_hmac('sha256', $xid . "&" . $xtimestamp, $secret_key, true);
        $xsignature = base64_encode($sign);

        // $apiURL = 'http://135.148.145.64:83/prolims/api/lis/getResult?ONO=220915/37334L';
        $apiURL = 'http://45.77.35.181:83/prolims/api/lis/order?startDate=20220916&endDate=20220916';

        $headers = [
            'X-id' => $xid,
            'X-timestamp' => $xtimestamp,
            'X-signature' => $xsignature,
        ];

        // $response = Http::withHeaders($headers)->get($apiURL);

        // $statusCode = $response->status();
        // $responseBody = json_decode($response->getBody(), true);

        $response = Http::withHeaders($headers)->get($apiURL)->json();
        dd($response);

=======
        // echo url('/')."/storage";
        // $now = date('Y-m-d');
        $now = Carbon::today()->toDateString();
        $kunjungan = Kunjungan::selectRaw('id')->get()->count();
        $view_hr_ini = Kunjungan::whereDate('created_at','>=', $now)->get()->count();
        $berita = Berita::selectRaw('id')->get()->count();
        $user = User::selectRaw('id')->get()->count();

        return response()->json(
            [
                'kunjungan'=>$kunjungan,
                'view_hr_ini'=>$view_hr_ini,
                'berita'=>$berita,
                'user'=>$user,
                'now'=>$now
            ]
        );
>>>>>>> 5a5865f34e9a0866bee773204e2d5343edd57e01
    }
}
