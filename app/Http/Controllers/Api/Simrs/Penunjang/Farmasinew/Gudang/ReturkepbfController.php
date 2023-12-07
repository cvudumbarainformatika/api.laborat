<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfheder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfrinci;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturkepbfController extends Controller
{
    public function simpanretur(Request $request)
    {
        DB::connection('farmasi')->select('call retur_pbf');
        $x = DB::connection('farmasi')->table('conter')->select('returpbf')->get();
        $wew = $x[0]->returpbf;
        $noretur = FormatingHelper::penerimaanobat($wew, '-RET-PBF');

        $simpan_h = Returpbfheder::updateorcreate(
            [
                'no_retur' => $request->noretur ?? $noretur,
                'nopenerimaan' => $request->nopenerimaan,
                'kdpbf' => $request->kdpbf,
                'gudang' => $request->gudang
            ],
            [
                'tgl_retur' => $request->tgl_retur,
                'no_faktur_retur_pbf' => $request->nofaktur,
                'tgl_faktur_retur_pbf' => $request->tgl_faktur,

                'no_kwitansi_pembayaran' => $request->nokwitansi,
                'tgl_kwitansi_pembayaran' => $request->tgl_kwitansi
            ]
        );
        if (!$simpan_h) {
            return new JsonResponse(['message' => 'Maaf retur Gagal Disimpan...!!!'], 500);
        }

        $simpan_r = Returpbfrinci::updateorcreate(
            [
                'no_retur' => $request->noretur ?? $noretur,
                'kd_obat' => $request->kd_obat,
                'jumlah_retur' => $request->jumlah_retur
            ],
            [
                'kondisi_barang' => $request->kondisi_barang,
                'tgl_rusak' => $request->tgl_rusak,
                'tgl_exp' => $request->tgl_exp
            ]
        );
        if (!$simpan_r) {
            Returpbfheder::where('no_retur', $noretur)->first()->delete();
            return new JsonResponse(['message' => 'Maaf retur Gagal Disimpan...!!!'], 500);
        }
        return new JsonResponse(['message' => 'Retur Berhasil Disimpan...!!!'], 200);
    }
}
