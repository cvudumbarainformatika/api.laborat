<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
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
            ->when(request('kd_ruang'), function ($q) {
                $q->where('gudang', request('kd_ruang'));
            })
            ->where('kunci', '1')
            ->get();
        // map kode perusahaan ke bentuk array, biar aman jika nanti ada append
        $temp = collect($raw)->map(function ($y) {
            return $y->kdpbf;
        });
        $data = KontrakPengerjaan::select('kodeperusahaan', 'namaperusahaan')->whereIn('kodeperusahaan', $temp)->distinct()->get();

        return new JsonResponse($data);
    }
    public function cariObat()
    {
        if (!request('kdpbf')) {
            return new JsonResponse(['message' => 'Tidak ada kode pbf']);
        }
        $kdruang = [request('kd_ruang')];
        $gudangs = ['Gd-05010100', 'Gd-03010100'];
        $ada = array_intersect($kdruang, $gudangs);
        if (count($ada) > 0) array_push($ada, '');
        // return new JsonResponse($ada);

        $data = Mobatnew::select(
            'new_masterobat.kd_obat',
            'new_masterobat.nama_obat',
            'new_masterobat.satuan_k',
        )
            ->leftJoin('penerimaan_r', 'penerimaan_r.kdobat', '=', 'new_masterobat.kd_obat')
            ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
            ->where('penerimaan_h.kdpbf', '=', request('kdpbf'))
            ->whereIn('new_masterobat.gudang',  $ada)
            ->get();
        return new JsonResponse($data);
    }
    public function ambilData()
    {

        $data = PenerimaanRinci::select(
            'fakturs.no_faktur',
            'fakturs.tgl_faktur',
            'penerimaan_h.jenissurat',
            'penerimaan_h.tgl_pembayaran',
            'penerimaan_h.tglsurat',
            'penerimaan_h.nomorsurat',
            'penerimaan_r.kdobat',
            'penerimaan_r.nopenerimaan',
            'penerimaan_r.no_batch',
            'penerimaan_r.tgl_exp',
            'penerimaan_r.harga_kcl as harga',
            'penerimaan_r.diskon',
            'penerimaan_r.ppn',
            'penerimaan_r.harga_netto_kecil as harga_neto',
            'penerimaan_r.jml_terima_k as jumlah',
            'penerimaan_r.kondisi_barang',
            'penerimaan_r.qty_tidak_baik',
        )
            ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
            ->leftJoin('fakturs', 'fakturs.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
            ->where('penerimaan_h.kdpbf', '=', request('kdpbf'))
            ->where('penerimaan_r.kdobat', '=', request('kd_obat'))
            ->with([
                'stokterima' => function ($st) {
                    $st->where('kdobat', '=', request('kd_obat'));
                },
                'stokadalwarsa' => function ($st) {
                    $st->whereDate('tglexp', '<', date('Y-m-d'))
                        ->where('kdobat', '=', request('kd_obat'));
                },
            ])
            ->get();
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
