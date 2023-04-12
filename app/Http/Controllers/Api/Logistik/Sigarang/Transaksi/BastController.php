<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Transaksi\Pemesanan\Pemesanan;
use App\Models\Sigarang\Transaksi\Penerimaan\DetailPenerimaan;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BastController extends Controller
{
    public function cariPerusahaan()
    {
        // ambil data kode perusahaan, masing2 satu aja
        $raw = Penerimaan::selectRaw('kode_perusahaan')
            ->where('tanggal_bast', null)
            ->orWhere('nilai_tagihan', '<=', 0)
            ->distinct()->get();

        // map ke bentuk array
        $temp = collect($raw)->map(function ($y) {
            return $y->kode_perusahaan;
        });

        // ambil data perusahaan tsh, cuma butuh nama dan kode perusahaan saja. masing2 perusahaan cuma butuh satu.
        $data = KontrakPengerjaan::select('kodeperusahaan', 'namaperusahaan')->whereIn('kodeperusahaan', $temp)->distinct()->get();

        return new JsonResponse($data);
    }

    public function cariPemesanan()
    {
        $data = Penerimaan::select('nomor')->distinct()
            ->where('kode_perusahaan', request('kode_perusahaan'))
            ->where(function ($a) {
                $a->where('tanggal_bast', null)
                    ->orWhere('nilai_tagihan', '<=', 0);
            })
            ->get();

        return new JsonResponse($data);
        // $anu['raw'] = $raw;
        // return new JsonResponse($anu);
    }

    public function ambilPemesanan()
    {
        $data = Pemesanan::where('nomor', request('nomor'))
            ->where('kode_perusahaan', request('kode_perusahaan'))
            ->with([
                'details',
                'penerimaan' => function ($anu) {
                    $anu->with('details')->where('tanggal_bast', null)
                        ->orWhere('nilai_tagihan', '<=', 0);
                }
            ])
            ->first();

        return new JsonResponse($data);
    }

    public function simpanBast(Request $request)
    {
        try {
            DB::beginTransaction();
            $berubah = [];
            foreach ($request->penerimaans as $penerimaan) {
                $data = Penerimaan::find($penerimaan['id']);
                $data->update([
                    'no_bast' => $request->no_bast,
                    'tanggal_bast' => $request->tanggal_bast,
                    'nilai_tagihan' => $penerimaan['nilai_tagihan'],
                ]);
                foreach ($penerimaan['details'] as $det) {
                    $detail = DetailPenerimaan::find($det['id']);
                    $detail->update([
                        'diskon' => $det['diskon'],
                        'harga_kontrak' => $det['harga_kontrak'],
                        'harga_jadi' => $det['harga_jadi'],
                        'ppn' => $det['ppn'],
                    ]);
                    $stok = RecentStokUpdate::where('no_penerimaan', $penerimaan['no_penerimaan'])
                        ->where('kode_rs', $detail['kode_rs'])
                        ->get();
                    if (count($stok) >= 0) {
                        foreach ($stok as $key) {
                            $key->update(['harga' => $det['harga_jadi']]);
                        }
                    }
                }
                if ($data->wasChanged()) {
                    array_push($berubah, $data);
                }
            }
            DB::commit();
            if (count($berubah) > 0) {
                return new JsonResponse(['message' => 'data Sudah di update', 'data' => $berubah], 200);
            }
            return new JsonResponse(['message' => 'data tidak berubah', 'data' => $berubah], 410);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
}
