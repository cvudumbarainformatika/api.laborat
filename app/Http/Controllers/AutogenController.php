<?php

namespace App\Http\Controllers;

use App\Events\PlaygroundEvent;
use App\Models\Berita;
use App\Models\Kunjungan;
use App\Models\LaboratLuar;
use App\Models\PemeriksaanLaborat;
use App\Models\TransaksiLaborat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

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
        // echo hash_hmac('sha256', '4444&1663225969','lisTest');

        // return URL::signedRoute('unsubscribe', ['user' => 1]);
        // return URL::temporarySignedRoute(
        //     'unsubscribe', now()->addMinutes(30), ['user' => 4334]
        // );
        // $groupped = PemeriksaanLaborat::selectRaw('rs21')->groupBy('rs21')->get()->pluck('rs21');
        // $query = collect(PemeriksaanLaborat::all());
        // $data= $query->groupBy('rs21');
        // $data = $gr->intersect($groupped);
        // $grouped = $query->mapToGroups(function ($item, $key) {
        //     return [
        //         $item['rs21'] => $item['rs2'],
        //     ];
        // });

        // $details = LaboratLuar::query()
        // ->selectRaw('
        //     nama, kelamin, alamat,
        //     nota,tgl,pengirim,hasil,hl,kd_lab,jml,hasil,tarif_sarana,tarif_pelayanan,
        //     (tarif_sarana + tarif_pelayanan) as biaya, ((tarif_sarana + tarif_pelayanan)* jml) as subtotal')
        // ->where('nota', '221001/81z6hyc-L')
        // ->with(['perusahaan', 'pemeriksaan_laborat'])->get();
        // $data= collect($details)->groupBy('pemeriksaan_laborat.rs21')
        // ->map(function ($item, $key) {
        //     return ['name'=>$key, 'child' => $item];
        // })->toArray();

        // for ($i=0; $i < count($data) ; $i++) {
        //     echo $data[$i];
        // }

        // $totNonPaket = $data['']->sum('subtotal');
        // $tot = $data->map(function($a){
        //     $sum = 0;
        //     if ($a->pemeriksaan_laborat->rs21 ==='') {
        //         $sum = $a->subtotal;
        //     }
        //     return $sum;
        // });
        // $total = 0;
        // foreach ($data as $key => $value) {
        //     // if ($value['name'] === '') {
        //     //     for ($i=0; $i < count($value) ; $i++) {
        //     //         $total = $value[$i]->subtotal;
        //     //     }
        //     //     // echo count($value);
        //     // }
        //     echo count($key['name']);
        // }

        // echo $total;


        // return response()->json($data);

        // $xid = "4444";
        // $secret_key = 'l15Test';
        // date_default_timezone_set('UTC');
        // $xtimestamp = strtotime('2022-09-16 14:12:49');
        // $sign = hash_hmac('sha256', $xid . "&" . $xtimestamp, $secret_key, true);
        // dd($sign);
        // $xsignature = base64_encode($sign);

        // $decodeb64 = base64_decode ( $xsignature ,false ) ;
        // echo '<pre>';
        // echo $sign;
        // echo '</pre>';
        // echo $xsignature;
        // echo '</pre>';
        // echo '<pre>';
        // echo $decodeb64;
        // echo '</pre>';
        // date_default_timezone_set('UTC');
        // $xtimestamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        // echo date('d M Y H:i:s',$xtimestamp);
        // $string = '4444';

        // DIPAKE DI MIDDLEWARE
        // date_default_timezone_set('Asia/Jakarta');
        // $xid = "4444";
        // $xtimestamp = time();
        // $secret_key = 'l15Test';
        // $sign = hash_hmac('sha256', $xid . "&" . $xtimestamp, $secret_key, true);
        // $xsignature = base64_encode($sign);

        // $signature = hash_hmac('sha256', '4444', 'l15Test');
        // $signature2 = hash_hmac('sha256', '1664810802', 'l15Test');
        // echo $xtimestamp;
        // return hash_equals($signature, (string) $signature2);


        // $from = '01'.'-'.date('m').'-'.date('Y');
        // $to = '31'.'-'.date('m').'-'.date('Y');
        // $lab = TransaksiLaborat::selectRaw('rs2')
        // ->whereMonth('rs3', '=', '09')
        // ->whereYear('rs3', '=', date('Y'))
        // ->groupBy('rs2')
        // ->orderBy('rs2', 'desc')->get()->count();
        // $xid = env('LIS_X_ID');
        // $secret_key = env('LIS_X_SECRET');
        // $signature = hash_hmac('sha256', $xid, $secret_key);
        // echo $signature;
        // echo date('Y-m-d', 1665488987);
        $query = collect($this->query_table());
        $data = $query->take(10);

        return new JsonResponse($data);
    }

    public function query_table()
    {
        $y = Carbon::now()->subYears(2);
        $query = TransaksiLaborat::query()
            ->selectRaw('rs1,rs2,rs3 as tanggal,rs20,rs8,rs23,rs18,rs21')
            ->groupBy('rs2')
            ->whereYear('rs3', '<', $y)
            ->filter(request(['q', 'periode', 'filter_by']))
            ->with([
                'kunjungan_poli',
                'kunjungan_rawat_inap',
                'kunjungan_poli.pasien',
                'kunjungan_poli.sistem_bayar',
                'kunjungan_rawat_inap.pasien',
                'kunjungan_rawat_inap.ruangan',
                'kunjungan_rawat_inap.sistem_bayar',
                'poli', 'dokter'
            ])
            ->orderBy('rs3', 'desc');

        return $query->get();
    }

    public function coba_api()
    {

        $xid = "4444";
        $secret_key = 'l15Test';
        date_default_timezone_set('UTC');
        $xtimestamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $sign = hash_hmac('sha256', $xid . "&" . $xtimestamp, $secret_key, true);
        $xsignature = base64_encode($sign);

        $apiURL = 'http://172.16.24.2:83/prolims/api/lis/postOrder';
        $postInput = [
            "ADDRESS" => "JL BANTARAN RT5/10 NO.07 SUMBERKEDAWUNG LECES - KOTA PROBOLINGGO",
            "BOD" => "19981127",
            "CLASS" => "-",
            "CLASS_NAME" => "-",
            "COMPANY" => "-",
            "COMPANY_NAME" => "RSUD MOCH SALEH",
            "DATE_ORDER" => "20220916141249",
            "DIAGNOSA" => "-",
            "DOCTOR" => "17",
            "DOCTOR_NAME" => "Abdul Muis, dr. Sp.THT",
            "GLOBAL_COMMENT" => "-",
            "IDENTITY_N" => "-",
            "IS_CITO" => "-",
            "KODE_PRODUCT" => "LAB183",
            "ONO" => "220915/37334L",
            "PATIENT_NAME" => "RAHMAD ARDIANSYAH",
            "EMAIL" => "aabb@aaa.com",
            "PATIENT_NO" => "120038",
            "ROOM" => "POL014",
            "ROOM_NAME" => "IRD",
            "SEX" => "1",
            "STATUS" => "N",
            "TYPE_PATIENT" => "-"
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
        $now = Carbon::now()->toDateTimeString();
        $xtimestamp = strval($now - strtotime('1970-01-01 00:00:00'));
        // $xtimestamp = strval(time() - strtotime($now));
        // $xtimestamp = strtotime($now);
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
        // dd($response);
        return response()->json($response);


        $xid = '4444';
        $xtimestamp = time();
        $secret_key = 'l15Test';
        $signature = hash_hmac('sha256', $xid, $secret_key);
    }



    public function coba_post_hasil(Request $request)
    {
        $request->validate([
            'ONO' => 'required',
            'GLOBAL_COMMENT' => 'required',
            'RESULT_LIST' => 'required',
        ]);

        if ($request->GLOBAL_COMMENT === 'laborat-luar') {
            # simpan laborat luar
            // L : 13-18, P : 12-16 g/dl
            $temp = collect($request->RESULT_LIST);
            foreach ($temp as $key) {
                LaboratLuar::where(['nota' => $request->ONO, 'kd_lab' => $key['KODE_PRODUCT']])->update([
                    'hasil' => $key['FLAGE'] . " : " . $key['REF_RANGE'] . " " . $key['UNIT']
                ]);
            }
        } else {
            $temp = collect($request->RESULT_LIST);
            foreach ($temp as $key) {
                TransaksiLaborat::where(['rs2' => $request->ONO, 'rs4' => $key['KODE_PRODUCT']])->update([
                    'rs21' => $key['FLAGE'] . " : " . $key['REF_RANGE'] . " " . $key['UNIT']
                ]);
            }
        }

        event(new PlaygroundEvent('coba'));
        return response()->json(['message' => 'success'], 201);
    }
}
