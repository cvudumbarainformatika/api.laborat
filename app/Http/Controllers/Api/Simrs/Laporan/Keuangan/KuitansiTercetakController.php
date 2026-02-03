<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Poli;
use App\Models\Simrs\Kasir\Karcis;
use App\Models\Simrs\Kasir\Kwitansilog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KuitansiTercetakController extends Controller
{
    public function kuitansiTercetak()
    {
        $tgldari = request('tgldari').' 00:00:00';
        $tglsampai = request('tglsampai').' 23:59:59';
        $layanan = request('layanan');

        if($layanan === '1'){
            // $data = Kwitansilog::where('flag', 'Kasir Rajal')->whereBetween('tgl', [$tgldari, $tglsampai])
            // ->where('userid', request('kasir'))
            // ->get();

            // Karcis::where('flag', 'Kasir Rajal')->whereBetween('tgl', [$tgldari, $tglsampai])
            // ->where('userid', request('kasir'))
            // ->get();
            $kwitansi = DB::table('kwitansilog')
                ->select([
                    'noreg',
                    'norm',
                    'nama',
                    'nokwitansi',
                    'tglx as tgl',
                    'userid',
                    'batal',
                    'tgl_batal',
                    'total',
                    DB::raw("'KWITANSI' as sumber")
                ])
                ->where('flag', 'Kasir Rajal')
                ->whereBetween('tgl', [$tgldari, $tglsampai])
                ->where('userid', request('kasir'));

            $karcis = DB::table('karcislog')
                ->select([
                    'noreg',
                    'norm',
                    'nama',
                    'nokarcis as nokuitansi',
                    'tglx as tgl',
                    'users',
                    'batal',
                     'tgl_batal',
                    'total',
                    DB::raw("'KARCIS' as sumber")
                ])
                // ->where('flag', 'Kasir Rajal')
                ->whereBetween('tgl', [$tgldari, $tglsampai])
                ->where('users', request('kasir'));

            $data = $kwitansi
                ->unionAll($karcis)   // pakai unionAll biar tidak buang data duplikat
                ->orderBy('tgl', 'asc')
                ->get();
            return new JsonResponse($data);
        }
    }
}
