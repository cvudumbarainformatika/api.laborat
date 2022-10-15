<?php

namespace App\Http\Controllers\Api\penunjang;

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

class TransaksiLaboratController extends Controller
{
    public function index()
    {
        $query = $this->query_table('table')
            ->with([
                'kunjungan_poli',
                // 'kunjungan_poli.pasien',
                'kunjungan_rawat_inap',
                // 'kunjungan_rawat_inap.pasien',
                // 'kunjungan_poli.sistem_bayar',
                // 'kunjungan_rawat_inap.sistem_bayar',
                'sb_kunjungan_poli',
                'sb_kunjungan_rawat_inap',
                'kunjungan_rawat_inap.ruangan',
                'poli',
                'dokter',
                'pasien_kunjungan_poli',
                'pasien_kunjungan_rawat_inap'
            ]);
        $data = $query->simplePaginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function totalData()
    {
        $data = $this->query_table('total')->get()->count();
        return new JsonResponse($data);
    }

    public function query_table($val)
    {
        $y = Carbon::now()->subYears(3);
        $query = TransaksiLaborat::query();
        if ($val === 'total') {
            $select = $query->selectRaw('rs2');
        } else {
            $select = $query->selectRaw('rs1,rs2,rs3 as tanggal,rs20,rs8,rs23,rs18,rs21');
        }
        $q = $select
            ->whereYear('rs3', '>=', $y)
            ->filter(request(['q', 'periode', 'filter_by']))
            ->orderBy('rs3', 'desc')->groupBy('rs2');
        return $q;
    }

    public function get_details()
    {
        $data = TransaksiLaborat::where('rs2', request('nota'))
            ->with('pemeriksaan_laborat')->get();

        return new JsonResponse($data);
    }

    public function kirim_ke_lis(Request $request)
    {
        $xid = "4444";
        $secret_key = 'l15Test';
        date_default_timezone_set('UTC');
        $xtimestamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $sign = hash_hmac('sha256', $xid . "&" . $xtimestamp, $secret_key, true);
        $xsignature = base64_encode($sign);

        $apiURL = 'http://172.16.24.2:83/prolims/api/lis/postOrder';


        $headers = [
            'X-id' => $xid,
            'X-timestamp' => $xtimestamp,
            'X-signature' => $xsignature,
        ];

        $response = Http::withHeaders($headers)->post($apiURL, $request->all());
        if (!$response) {
            return response()->json([
                'message' => 'Harap Ulangi... LIS ERROR'
            ], 500);
        }

        $statusCode = $response->status();
        $responseBody = json_decode($response->getBody(), true);

        TransaksiLaborat::where('rs2', $request->ONO)->update(['rs18' => "1"]);

        return response()->json($responseBody);
    }
}
