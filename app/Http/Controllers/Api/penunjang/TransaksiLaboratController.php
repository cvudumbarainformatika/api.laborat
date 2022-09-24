<?php

namespace App\Http\Controllers\Api\penunjang;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\TransaksiLaborat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiLaboratController extends Controller
{
    public function index()
    {
        $query = TransaksiLaborat::query()
                ->selectRaw('rs1,rs2,rs3 as tanggal,rs20,rs8,rs23,rs18')
                ->filter(request(['q','periode']))
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
                ->groupBy('rs2')
                ->orderBy('rs2', 'desc');
                // ->whereDate('rs3', '=', $now);
        $data = $query->simplePaginate(request('per_page'));
        // $count = TransaksiLaborat::query()->selectRaw('rs2')
        // ->filter(request(['q','periode']))
        // ->groupBy('rs2')->get()->count();

       return new JsonResponse($data);
    }

    public function totalData()
    {
       $data = TransaksiLaborat::query()
        ->selectRaw('rs2')
        ->filter(request(['q','periode']))
        ->groupBy('rs2')
        ->get()->count();
        return new JsonResponse($data);
    }

    public function get_details()
    {
        $data = TransaksiLaborat::where('rs2', request('nota'))
        ->with('pemeriksaan_laborat')->get();

        return new JsonResponse($data);
    }
}
