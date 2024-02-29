<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Pemesanan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliH;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliR;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DialogrencanapemesananController extends Controller
{
    public function dialogrencanabeli()
    {
        $rencanabeli = RencanabeliH::select(
            'perencana_pebelian_h.no_rencbeliobat',
            'perencana_pebelian_h.no_rencbeliobat as noperencanaan',
            'perencana_pebelian_h.kd_ruang',
            'perencana_pebelian_h.tgl as tglperencanaan',
            'perencana_pebelian_r.kdobat as kdobat',
            'perencana_pebelian_r.stok_real_gudang as stokgudang',
            'perencana_pebelian_r.stok_real_rs as stokrs',
            'perencana_pebelian_r.stok_max_rs as stomaxkrs',
            'perencana_pebelian_r.jumlah_bisa_dibeli',
            'perencana_pebelian_r.jumlah_diverif',
            'perencana_pebelian_r.jumlahdirencanakan as jumlahdipesandiperencanaan',
            'new_masterobat.nama_obat as namaobat',
            'new_masterobat.status_generik as status_generik',
            'new_masterobat.status_fornas as status_fornas',
            'new_masterobat.status_forkid as status_forkid',
            'new_masterobat.status_kronis',
            'new_masterobat.status_prb',
            'new_masterobat.sistembayar as sistembayar',
            'new_masterobat.satuan_b as satuan_b',
            'new_masterobat.satuan_k as satuan_k',
            'perencana_pebelian_r.flag as flagperobat',
            'perencana_pebelian_h.kd_ruang as gudang',
            DB::raw('sum(pemesanan_r.jumlahdpesan) as jumlahallpesan')
        )
            ->leftjoin('perencana_pebelian_r', 'perencana_pebelian_h.no_rencbeliobat', '=', 'perencana_pebelian_r.no_rencbeliobat')
            ->leftjoin('new_masterobat', 'perencana_pebelian_r.kdobat', '=', 'new_masterobat.kd_obat')
            ->leftjoin('pemesanan_r', 'new_masterobat.kd_obat', '=', 'pemesanan_r.kdobat')
            ->where('perencana_pebelian_h.flag', '2')
            ->where('perencana_pebelian_r.flag', '')
            ->where('perencana_pebelian_h.no_rencbeliobat', 'Like', '%' . request('no_rencbeliobat') . '%')
            ->with([
                'rincian' => function ($re) {
                    $re->select('no_rencbeliobat', 'kdobat')
                        ->with([
                            'penerimaan' => function ($pen) {
                                $pen->select(
                                    'kdobat',
                                    'harga_kcl as harga'
                                )
                                    ->orderBy('id', 'DESC')
                                    ->limit(1);
                            },
                            'stok' => function ($pen) {
                                $pen->select(
                                    'kdobat',
                                    'harga'
                                )
                                    ->orderBy('id', 'DESC')
                                    ->limit(1);
                            }
                        ])
                        ->where('flag', '');
                }
            ])
            ->groupby('perencana_pebelian_h.no_rencbeliobat', 'perencana_pebelian_r.kdobat')
            ->orderBy('perencana_pebelian_h.tgl')->paginate(request('per_page'));

        // $rencanabeli = RencanabeliH::with(
        //     'rincian',
        //     'rincian.mobat'
        // )->where('no_rencbeliobat', 'LIKE', '%' . request('no_rencbeliobat') . '%')
        //     ->orderBy('tgl', 'desc')
        //     ->get();

        return new JsonResponse($rencanabeli);
    }

    public function dialogrencanabeli_rinci()
    {
        $rencanabelirinci = RencanabeliR::with(['mobat'])->where('no_rencbeliobat', request('norencanabeliobat'))->get();
        return new JsonResponse($rencanabelirinci);
    }
}
