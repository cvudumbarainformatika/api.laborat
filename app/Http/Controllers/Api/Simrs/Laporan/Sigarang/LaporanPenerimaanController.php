<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Rekening50;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanPenerimaanController extends Controller
{
    public function lappenerimaan()
    {
        $tgl = request('tgl');
        $tglx = request('tglx');
        $rek50 = Rekening50::select(
            'rekening50s.kode as kode',
            'rekening50s.uraian as uraian50',
        )->with(['rincianpenerimaan' => function ($rincianpenerimaan) use ($tgl, $tglx) {
            $rincianpenerimaan->select(
                'kode_50',
                DB::raw('sum(detail_penerimaans.qty*detail_penerimaans.harga) as subtotal'),
                'detail_penerimaans.kode_108 as kode_108',
                'detail_penerimaans.uraian_108 as uraian_108',
                'detail_penerimaans.kode_rs as kode_rs',
                'detail_penerimaans.nama_barang as nama_barang'
            )
                ->join('penerimaans', 'penerimaans.id', '=', 'detail_penerimaans.penerimaan_id')
                ->whereBetween('penerimaans.tanggal', [$tgl, $tglx])
                ->groupBy('detail_penerimaans.kode_rs');
        }])

            ->Where('rekening50s.jenis', '02')->where('rekening50s.objek', '01')
            ->get();
        //$wew[] = $rek50[0]->kode50cari;
        return $rek50;
        // $rek50x = Rekening50::select(
        //     'rekening50s.kode as kode50',
        //     'rekening50s.uraian as uraian50'

        // )
        //     ->whereIn('rekening50s.kode50', $wew)
        //     ->get();

        // return $rek50x;

        // $judulsatu = Penerimaan::select(
        //     DB::raw('SUBSTRING_INDEX(detail_penerimaans.kode_50,".",4) as kode50'),
        //     'detail_penerimaans.uraian_50 as uraian50',
        //     DB::raw('sum(detail_penerimaans.qty*detail_penerimaans.harga) as total')
        // )
        //     ->join('detail_penerimaans', 'penerimaans.id', '=', 'detail_penerimaans.penerimaan_id')
        //     ->with('details.penerimaan')
        //     ->whereBetween('penerimaans.tanggal', [$tgl, $tglx])
        //     ->groupBy(DB::raw('SUBSTRING_INDEX(detail_penerimaans.kode_50,".",4)'))
        //     ->get();

        // return new JsonResponse($judulsatu);
    }
}
