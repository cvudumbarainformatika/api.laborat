<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfheder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfrinci;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturkepbfController extends Controller
{

    public function cariPerusahaan()
    {
        // ambil perusahaan yang sedang belum bast
        $raw = PenerimaanHeder::select('kdpbf')
            ->where(function ($q) {
                $q->whereNull('tgl_bast')
                    ->orWhere('jumlah_bast', '<=', 0);
            })
            ->where('jenis_penerimaan', 'Pesanan')
            ->where('kunci', '1')
            ->get();
        // map kode perusahaan ke bentuk array, biar aman jika nanti ada append
        $temp = collect($raw)->map(function ($y) {
            return $y->kdpbf;
        });
        $data = KontrakPengerjaan::select('kodeperusahaan', 'namaperusahaan')->whereIn('kodeperusahaan', $temp)->distinct()->get();

        return new JsonResponse($data);
    }
    public function simpanretur(Request $request)
    {
        if ($request->noretur == '' || $request->noretur == null) {
            DB::connection('farmasi')->select('call retur_pbf(@nomor)');
            $x = DB::connection('farmasi')->table('conter')->select('returpbf')->get();
            $wew = $x[0]->returpbf;
            $noretur = FormatingHelper::penerimaanobat($wew, '-RET-PBF');
        } else {
            $noretur = $request->noretur;
        }

        $simpan_h = Returpbfheder::updateOrCreate(
            [
                'no_retur' => $noretur,
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

        $simpan_r = Returpbfrinci::updateOrCreate(
            [
                'no_retur' => $noretur,
                'kd_obat' => $request->kd_obat,
                'jumlah_retur' => $request->jumlah_retur
            ],
            [
                'kondisi_barang' => $request->kondisi_barang,
                'tgl_rusak' => $request->tgl_rusak,
                'tgl_exp' => $request->tgl_exp
            ]
        );

        return new JsonResponse(
            [
                'noretur' => $noretur,
                'heder' => $simpan_h,
                'rinci' => $simpan_r->load('mobatnew'),
                'message' => 'Retur Berhasil Disimpan...!!!'
            ],
            200
        );
    }
}
