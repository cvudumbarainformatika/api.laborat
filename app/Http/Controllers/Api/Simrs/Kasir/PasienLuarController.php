<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
use App\Models\LaboratLuar;
use App\Models\Simrs\Kasir\Kwitansilog;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Radiologi\RadiologiLuar;
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

        if ($jenislayanan === 'FARMASI') {

            $data = Resepkeluarheder::with(['rincian'])
                ->select('farmasi.resep_keluar_h.*','farmasi.resep_keluar_h.noresep as nota','farmasi.resep_keluar_h.nama_pejabat as nama')
                ->selectRaw("'FARMASI' as jenislayanan")
                ->when($term, function ($q) use ($term) {
                    $q->where(function ($sub) use ($term) {
                        $sub->where('farmasi.resep_keluar_h.noresep', 'like', "%{$term}%")
                            ->orWhere('farmasi.kunjungan_penjualans.nama', 'like', "%{$term}%");
                    });
                })
                ->when($status === '' || $status === null, function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNull('farmasi.resep_keluar_h.flag_pembayaran')
                            ->orWhere('farmasi.resep_keluar_h.flag_pembayaran', '');
                    });
                })
                ->when($status !== null && $status !== '' && $status !== 'SEMUA', function ($q) use ($status) {
                    $q->where('farmasi.resep_keluar_h.flag_pembayaran', $status);
                })
                ->when($tgldari && $tglsampai, function ($q) use ($tgldari, $tglsampai) {
                    $q->whereBetween('farmasi.resep_keluar_h.tgl', [$tgldari, $tglsampai]);
                })
                ->where('farmasi.resep_keluar_h.tiperesep', 'penjualan')
                ->orderBy('farmasi.resep_keluar_h.tgl', 'desc')
                ->paginate($perpage);

            // 🔹 Hitung subtotal per nota
            $data->getCollection()->transform(function ($item) {
                $item->subtotal = $item->rincian->sum(function ($r) {
                    return $r->jumlah * $r->harga_jual;
                });
                return $item;
            });

        }else if($jenislayanan === 'LABORAT'){
            $data = LaboratLuar::select(
                'lab_luar.nota as noreg',
                'lab_luar.nota as nota',
                'lab_luar.nama',
                'lab_luar.tgl',
                DB::raw("
                    (
                        -- rs21 kosong → jumlahkan semua
                        SUM(
                            CASE
                                WHEN rs49.rs21 = ''
                                THEN jml * (tarif_sarana + tarif_pelayanan)
                                ELSE 0
                            END
                        )
                        +
                        -- rs21 ada isi → ambil SATU record saja
                        MAX(
                            CASE
                                WHEN rs49.rs21 <> ''
                                THEN jml * (tarif_sarana + tarif_pelayanan)
                                ELSE 0
                            END
                        )
                    ) AS subtotal
                ")
            )
            ->selectRaw("'LABORAT' as jenislayanan")
            ->when($term, function ($q) use ($term) {
                $q->where('lab_luar.nota', 'like', "%{$term}%")
                ->orWhere('lab_luar.pasien', 'like', "%{$term}%");
            })
            ->join('rs49', 'rs49.rs1', '=', 'lab_luar.kd_lab')
            ->whereBetween('lab_luar.tgl', [$tgldari, $tglsampai])
            ->groupBy(
                'lab_luar.nota',
                'lab_luar.nama',
                'lab_luar.tgl'
            )
            ->orderBy('lab_luar.tgl', 'desc')
            ->paginate($perpage);


        }else if($jenislayanan === 'RADIOLOGI'){
            $data = RadiologiLuar::whereBetween('rs270.rs8', [$tgldari, $tglsampai])
            ->select('rs270.rs1 as noreg', 'rs270.rs1 as nota', 'rs270.rs2 as nama', 'rs270.rs8 as tgl')
            ->selectRaw("sum(rs271.rs5 + rs271.rs6) as subtotal")
            ->selectRaw("'RADIOLOGI' as jenislayanan")
            ->leftJoin('rs271', 'rs271.rs1', '=', 'rs270.rs1')
            ->when($term, function ($q) use ($term) {
                $q->where('nota', 'like', "%{$term}%")
                ->orWhere('pasien', 'like', "%{$term}%");
            })
            ->groupBy( 'rs270.rs1')
            ->orderBy('rs270.rs8', 'desc')
            ->paginate($perpage);
        }

        return new JsonResponse($data);
    }
}
