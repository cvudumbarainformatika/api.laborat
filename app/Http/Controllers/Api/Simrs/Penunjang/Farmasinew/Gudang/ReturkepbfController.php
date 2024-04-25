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
            ->distinct('kdpbf')
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
            'penerimaan_h.tglpenerimaan',
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
        )
            ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
            ->leftJoin('fakturs', 'fakturs.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
            ->where('penerimaan_h.kdpbf', '=', request('kdpbf'))
            ->where('penerimaan_r.kdobat', '=', request('kd_obat'))
            ->when(request('nopenerimaan'), function ($q) {
                $q->where('penerimaan_r.nopenerimaan', 'LIKE', '%' . request('nopenerimaan') . '%');
            })
            ->with([
                'stokterima' => function ($st) {
                    $st->where('kdobat', '=', request('kd_obat'));
                },
            ])
            ->orderBy('penerimaan_h.tglpenerimaan', 'DESC')
            ->orderBy('penerimaan_h.nopenerimaan', 'DESC')
            ->limit(20)
            ->get();
        return new JsonResponse($data);
    }
    public function simpanretur(Request $request)
    {
        // $data = [
        //     'no_retur' => '$noretur',
        //     'nopenerimaan' => $request->item['nopenerimaan'],
        //     'kdpbf' => $request->kdpbf,
        //     'gudang' => $request->kd_ruang,

        //     'tgl_retur' => $request->tgl_retur,
        //     'no_faktur_retur_pbf' => $request->no_faktur_retur_pbf ?? '',
        //     'tgl_faktur_retur_pbf' => $request->tgl_faktur_retur_pbf ?? null,

        //     'no_kwitansi_pembayaran' => $request->no_kwitansi_pembayaran ?? '',
        //     'tgl_kwitansi_pembayaran' => $request->tgl_kwitansi_pembayaran ?? null,

        //     'no_retur' => '$noretur',
        //     'kd_obat' => $request->item['kdobat'],
        //     'jumlah_retur' => $request->item['jumlah_retur'],

        //     'kondisi_barang' => $request->item['kondisi_barang'],
        //     'tgl_rusak' => $request->item['kdobat'],
        //     'tgl_exp' => $request->item['tgl_exp'],
        // ];

        // return new JsonResponse([
        //     'data' => $data,
        //     'message' => 'Cek Data cuy'
        // ]);

        try {
            DB::connection('farmasi')->beginTransaction();
            if ($request->no_retur == '' || $request->no_retur == null) {
                DB::connection('farmasi')->select('call retur_pbf(@nomor)');
                $x = DB::connection('farmasi')->table('conter')->select('returpbf')->get();
                $wew = $x[0]->returpbf;
                $no_retur = FormatingHelper::penerimaanobat($wew, '-RET-PBF');
            } else {
                $no_retur = $request->no_retur;
            }

            $simpan_h = Returpbfheder::updateOrCreate(
                [
                    'no_retur' => $no_retur,
                    'kdpbf' => $request->kdpbf,
                    'gudang' => $request->kd_ruang
                ],
                [
                    'tgl_retur' => $request->tgl_retur,
                    'no_faktur_retur_pbf' => $request->no_faktur_retur_pbf ?? '',
                    'tgl_faktur_retur_pbf' => $request->tgl_faktur_retur_pbf ?? null,

                    'no_kwitansi_pembayaran' => $request->no_kwitansi_pembayaran ?? '',
                    'tgl_kwitansi_pembayaran' => $request->tgl_kwitansi_pembayaran ?? null
                ]
            );
            if (!$simpan_h) {
                return new JsonResponse(['message' => 'Maaf retur Gagal Disimpan...!!!'], 500);
            }

            $simpan_r = Returpbfrinci::updateOrCreate(
                [
                    'no_retur' => $no_retur,
                    'kd_obat' => $request->item['kdobat'],
                    'nopenerimaan' => $request->item['nopenerimaan'],
                ],
                [
                    'jumlah_retur' => $request->item['jumlah_retur'],
                    'kondisi_barang' => $request->item['kondisi_barang'],
                    'tgl_rusak' => $request->item['tgl_rusak'] ?? date('Y-m-d'),
                    'harga_net' => $request->item['harga_neto'],
                    'subtotal' => $request->item['subtotal'],
                    'tgl_exp' => $request->item['tgl_exp']
                ]
            );
            DB::connection('farmasi')->commit();
            return new JsonResponse(
                [
                    'no_retur' => $no_retur,
                    'heder' => $simpan_h,
                    'rinci' => $simpan_r->load('mobatnew:kd_obat,nama_obat'),
                    'message' => 'Retur Berhasil Disimpan...!!!'
                ],
                200
            );
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 410);
        }
    }
}
