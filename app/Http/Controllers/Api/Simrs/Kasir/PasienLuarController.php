<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class PasienLuarController extends Controller
{
    public function getbill(Request $request)
    {
        $jenislayanan = $request->jenislayanan;
        $term         = $request->q;
        $perpage      = $request->perpage ?? 10;
        $tgldari      = $request->daritgl;
        $tglsampai    = $request->sampaitgl;
        $status       = $request->status;

        if($jenislayanan === 'FARMASI'){
            $data = DB::table('farmasi.resep_keluar_h')
            ->select([
                'farmasi.resep_keluar_h.noresep as nota',
                'farmasi.resep_keluar_h.tgl as tgl',
                DB::raw("'FARMASI' as jenislayanan"),

                DB::raw('ROUND(
                    COALESCE(SUM(
                        farmasi.resep_keluar_r.harga_jual *
                        farmasi.resep_keluar_r.jumlah +
                        farmasi.resep_keluar_r.nilai_r
                    ), 0)
                ) as subtotal'),

                'farmasi.kunjungan_penjualans.noreg as noreg',
                'farmasi.resep_keluar_h.norm as norm',
                'farmasi.kunjungan_penjualans.nama as nama',

                // kolom kwitansi (PILIH JANGAN *)
                // 'rs.kwitansilog.no_kwitansi',
                // 'rs.kwitansilog.tgl_kwitansi',
                // 'rs.kwitansilog.total_bayar',
            ])
            ->leftJoin(
                'farmasi.resep_keluar_r',
                'farmasi.resep_keluar_h.noresep',
                '=',
                'farmasi.resep_keluar_r.noresep'
            )
            ->leftJoin(
                'farmasi.kunjungan_penjualans',
                'farmasi.kunjungan_penjualans.noreg',
                '=',
                'farmasi.resep_keluar_h.noreg'
            )
            // ->leftJoin(
            //     'rs.kwitansilog',
            //     'rs.kwitansilog.nota',
            //     '=',
            //     'farmasi.resep_keluar_h.noresep'
            // )
            ->where('farmasi.resep_keluar_h.tiperesep', 'penjualan')

            // 🔍 SEARCH
            ->when(!empty($term), function ($q) use ($term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('farmasi.resep_keluar_h.noresep', 'like', "%{$term}%")
                        ->orWhere('farmasi.kunjungan_penjualans.nama', 'like', "%{$term}%");
                });
            })

            // 💰 STATUS BAYAR
            ->when($status === '' || $status === null, function ($q) {
                $q->whereNull('farmasi.resep_keluar_h.flag_pembayaran')
                ->orWhere('farmasi.resep_keluar_h.flag_pembayaran', '');
            })
            ->when($status !== '' && $status !== null && $status !== 'SEMUA', function ($q) use ($status) {
                $q->where('farmasi.resep_keluar_h.flag_pembayaran', $status);
            })

            // 📅 FILTER TANGGAL
            ->when($tgldari && $tglsampai, function ($q) use ($tgldari, $tglsampai) {
                $q->whereBetween('farmasi.resep_keluar_h.tgl', [$tgldari, $tglsampai]);
            })

            // ✅ GROUP BY LENGKAP
            ->groupBy(
                'farmasi.resep_keluar_h.noresep',
                'farmasi.resep_keluar_h.tgl',
                'farmasi.kunjungan_penjualans.noreg',
                'farmasi.resep_keluar_h.norm',
                'farmasi.kunjungan_penjualans.nama',
                // 'rs.kwitansilog.no_kwitansi',
                // 'rs.kwitansilog.tgl_kwitansi',
                // 'rs.kwitansilog.total_bayar'
            )

            ->orderBy('farmasi.resep_keluar_h.tgl', 'desc')
            ->paginate($perpage);
        }
        return new JsonResponse(['data' => $data]);
    }
}
