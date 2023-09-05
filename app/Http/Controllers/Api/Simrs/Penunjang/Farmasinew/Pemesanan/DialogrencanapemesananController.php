<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Pemesanan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliH;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliR;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DialogrencanapemesananController extends Controller
{
    public function dialogrencanabeli()
    {
        // $rencanabeli = RencanabeliH::select(
        //     'perencana_pebelian_h.no_rencbeliobat as noperencanaan',
        //     'perencana_pebelian_h.tgl as tglperencanaan',
        //     'perencana_pebelian_r.kdobat as kdobat',
        //     'perencana_pebelian_r.stok_real_gudang as stokgudang',
        //     'perencana_pebelian_r.stok_real_rs as stokrs',
        //     'perencana_pebelian_r.stok_max_rs as stomaxkrs',
        //     'perencana_pebelian_r.jumlah_bisa_dibeli as jumlahdipesan',
        //     'new_masterobat.nama_obat as namaobat',
        //     'new_masterobat.status_generik as status_generik',
        //     'new_masterobat.status_fornas as status_fornas',
        //     'new_masterobat.status_forkid as status_forkid',
        //     'new_masterobat.sistembayar as sistembayar',
        //     'pemesanan_r.jumlahdpesan'
        // )
        //     ->leftjoin('perencana_pebelian_r', 'perencana_pebelian_h.no_rencbeliobat', '=', 'perencana_pebelian_r.no_rencbeliobat')
        //     ->leftjoin('new_masterobat', 'perencana_pebelian_r.kdobat', '=', 'new_masterobat.kd_obat')
        //     ->leftjoin('pemesanan_r', 'new_masterobat.kd_obat', '=', 'pemesanan_r.kdobat')
        //     ->where('perencana_pebelian_h.flag', '1')->where('perencana_pebelian_r.flag', '')
        //     ->where('new_masterobat.nama_obat', 'Like', '%' . request('namaobat') . '%')
        //     ->groupby('new_masterobat.kd_obat', ',perencana_pebelian_h.no_rencbeliobat')
        //     ->orderBy('perencana_pebelian_h.tgl')->get();

        $rencanabeli = RencanabeliH::with([
            'rincian',
            'rincian.mobat',
            'rincian' => function ($anu) {
                $anu->leftjoin('pemesanan_r', function ($join) {
                    $join->select(
                        'pemesanan_r.jumlahdpesan as jumlahDipesan',
                        'pemesanan_r.noperencanaan',
                        'pemesanan_r.kdobat as kode',
                        'perencana_pebelian_r.kdobat',
                        'perencana_pebelian_r.no_rencbeliobat',
                        'perencana_pebelian_r.flag',
                        'perencana_pebelian_r.jumlahdpesan',
                    );
                    $join->on('pemesanan_r.noperencanaan', '=', 'perencana_pebelian_r.no_rencbeliobat')
                        ->on('pemesanan_r.kdobat', '=', 'perencana_pebelian_r.kdobat');
                });
            },
        ])->where('no_rencbeliobat', 'LIKE', '%' . request('no_rencbeliobat') . '%')
            ->orderBy('tgl', 'desc')
            ->get();

        return new JsonResponse($rencanabeli);
    }

    public function dialogrencanabeli_rinci()
    {
        $rencanabelirinci = RencanabeliR::with(['mobat'])->where('no_rencbeliobat', request('norencanabeliobat'))->get();
        return new JsonResponse($rencanabelirinci);
    }
    public function anu()
    {
        $rencanabeli = RencanabeliH::with([
            'rincian',
            'rincian.mobat',
            'rincian' => function ($anu) {
                $anu->leftjoin('pemesanan_r', function ($join) {
                    $join->select(
                        'pemesanan_r.jumlahdpesan as jumlahDipesan',
                        'pemesanan_r.noperencanaan',
                        'pemesanan_r.kdobat as kode',
                        'perencana_pebelian_r.kdobat',
                        'perencana_pebelian_r.no_rencbeliobat',
                        'perencana_pebelian_r.flag',
                        'perencana_pebelian_r.jumlahdpesan',
                    );
                    $join->on('pemesanan_r.noperencanaan', '=', 'perencana_pebelian_r.no_rencbeliobat')
                        ->on('pemesanan_r.kdobat', '=', 'perencana_pebelian_r.kdobat');
                });
            },
        ])->where('no_rencbeliobat', 'LIKE', '%' . request('no_rencbeliobat') . '%')
            ->orderBy('tgl', 'desc')
            ->get();

        return new JsonResponse($rencanabeli);
    }
}
