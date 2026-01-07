<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
use App\Models\LaboratLuar;
use App\Models\Simrs\Kasir\Kwitansilog;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
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
            $data = LaboratLuar::select('nota as nota','nama as nama','tgl')
                ->selectRaw("'LABORAT' as jenislayanan")
                ->when($term, function ($q) use ($term) {
                    $q->where('nota', 'like', "%{$term}%")
                        ->orWhere('pasien', 'like', "%{$term}%");
                })
                ->whereBetween('tgl', [$tgldari, $tglsampai])
                // ->where('status', '!=', 'LUNAS')
                ->groupBy('nota')
                ->orderBy('tgl', 'desc')
                ->paginate($perpage);
        }

        return new JsonResponse($data);
    }
}
