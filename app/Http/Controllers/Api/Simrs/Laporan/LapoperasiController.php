<?php

namespace App\Http\Controllers\Api\Simrs\Laporan;

use App\Http\Controllers\Controller;
use App\Models\KunjunganPoli;
use App\Models\Simrs\Laporan\Operasi\LaporanOperasi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LapoperasiController extends Controller
{
    public function lapoperasirr()
    {
        $from=request('from');
        $to=request('to');
        $query = LaporanOperasi::with([
            'permintaanoperasi:rs1',
            'pasien_kunjungan_poli:rs15.rs1 as norm,rs15.rs2 as nama',
            'pasien_kunjungan_rawat_inap:rs15.rs1 as norm,rs15.rs2 as nama'])
            //->whereMonth('rs217.rs3','='.$bln)
            //->whereYear('rs3','='. $thn)
            ->whereBetween('rs217.rs3', [$from, $to])
            ->paginate(10);

        // $query = LaporanOperasi::with(['permintaanoperasi:rs1'])
        // ->limit(100)->get();
        return new JsonResponse($query, 200);
    }
}
