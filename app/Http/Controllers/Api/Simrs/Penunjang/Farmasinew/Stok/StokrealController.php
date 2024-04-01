<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokrealController extends Controller
{
    public static function stokreal($nopenerimaan, $request)
    {
        //return ($request->kdobat);
        $simpanstokreal = Stokrel::updateOrCreate(
            [
                'nopenerimaan' => $nopenerimaan,
                'kdobat' => $request->kdobat,
                'kdruang' => $request->kdruang,
                'nobatch' => $request->no_batch,
                'tglexp' => $request->tgl_exp,
                'harga' => $request->harga_netto_kecil
            ],
            [
                'tglpenerimaan' => $request->tglpenerimaan,
                'jumlah' => $request->jml_terima_k,
                'flag' => 1

            ]
        );
        if (!$simpanstokreal) {
            return new JsonResponse(['message' => 'not ok'], 500);
        }
        return 200;
    }

    public static function updatestokgudangdandepo($request)
    {
        $jml_dikeluarkan = (int) $request->jumlah_diverif;
        $totalstok = Stokrel::select(DB::raw('sum(stokreal.jumlah) as totalstok'))
            ->where('kdobat', $request->kdobat)
            ->where('kdruang', $request->kdruang)
            ->where('jumlah', '!=', 0)
            ->groupBy('kdobat', 'kdruang')
            ->first();

        $totalstokx = (int) $totalstok->totalstok;
        if ($jml_dikeluarkan > $totalstokx) {
            return new JsonResponse(['message' => 'Maaf Stok Anda Tidak Mencukupi...!!!'], 500);
        }

        $caristokgudang = Stokrel::where('kdobat', $request->kdobat)
            ->where('kdruang', $request->kdruang)
            ->where('jumlah', '!=', 0)
            ->orderBy('tglexp')
            ->get();

        foreach ($caristokgudang as $val) {
            $jmlstoksatuan = $val['jumlah'];
            $nopenerimaan = $val['nopenerimaan'];
        }

        return [$nopenerimaan, $jmlstoksatuan];
    }

    public function insertsementara(Request $request)
    {
        $sementara = date('ymdhis');
        $ruang = $request->kdruang;
        if ($ruang === 'Gd-05010100') {
            $kdruang = 'GO';
        } elseif ($ruang === 'Gd-03010100') {
            $kdruang = 'FS';
        } elseif ($ruang === 'Gd-04010102') {
            $kdruang = 'DRI';
        } elseif ($ruang === 'Gd-05010101') {
            $kdruang = 'DRJ';
        } else {
            $kdruang = 'DKO';
        }
        $notrans = $sementara . '-' . $kdruang;

        // $simpanstok = Stokrel::create(
        //     [
        //         'nopenerimaan' => $request->notrans ?? $notrans,
        //         'tglpenerimaan' => $request->tglpenerimaan ?? date('Y-m-d H:i:s'),
        //         'kdobat' => $request->kdobat,
        //         'jumlah' => $request->jumlah,
        //         'kdruang' => $request->kdruang,
        //         'harga' => $request->harga ?? '',
        //         'tglexp' => $request->tglexp ?? '',
        //         'nobatch' => $request->nobatch ?? '',
        //     ]
        // );

        $simpanstokopname = Stokopname::create(
            [
                'nopenerimaan' => $request->notrans ?? $notrans,
                'tglpenerimaan' => $request->tglpenerimaan ?? date('Y-m-d H:i:s'),
                'kdobat' => $request->kdobat,
                'jumlah' => $request->jumlah,
                'kdruang' => $request->kdruang,
                'harga' => $request->harga ?? '',
                'tglexp' => $request->tglexp ?? '',
                'nobatch' => $request->nobatch ?? '',
                'tglopname' => '2023-12-31 23:59:59'
            ]
        );
        return new JsonResponse(
            [
                'datastok' => $simpanstokopname,
                'message' => 'Stok Berhasil Disimpan...!!!'
            ],
            200
        );
    }
    public function updatestoksementara(Request $request)
    {
        $cari = Stokopname::where('id', $request->id)->first();
        $cari->jumlah = $request->jumlah ?? '';
        $cari->harga = $request->harga ?? '';
        $cari->tglexp = $request->tglexp ?? '';
        $cari->nobatch = $request->nobatch ?? '';
        $cari->save();

        return new JsonResponse(['message' => 'Stok Berhasil Disimpan...!!!'], 200);
    }
    public function updatehargastok(Request $request)
    {
        $cari = Stokreal::where('id', $request->id)->first();
        $cari->jumlah = $request->jumlah ?? '';
        $cari->harga = $request->harga ?? '';
        $cari->tglexp = $request->tglexp ?? '';
        $cari->nobatch = $request->nobatch ?? '';
        $cari->save();

        return new JsonResponse(['message' => 'Stok Berhasil Disimpan...!!!'], 200);
    }

    public function liststokreal()
    {
        $kdruang = request('kdruang');
        $stokreal = Stokopname::select('stokopname.*', 'new_masterobat.*', 'stokopname.id as idx')->where('stokopname.flag', '')
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokopname.kdobat')
            ->where('stokopname.kdruang', $kdruang)
            ->where(function ($x) {
                $x->where('stokopname.nopenerimaan', 'like', '%' . request('q') . '%')
                    ->orwhere('stokopname.kdobat', 'like', '%' . request('q') . '%')
                    ->orwhere('new_masterobat.nama_obat', 'like', '%' . request('q') . '%');
            })
            ->when(request('from'), function ($q) {
                $q->whereBetween('tglopname', [request('from') . ' 23:00:00', request('to') . ' 23:59:59']);
            })
            ->paginate(request('per_page'));
        return new JsonResponse($stokreal);
    }
    public function listStokSekarang()
    {
        $kdruang = request('kdruang');
        $stokreal = Stokreal::select('stokreal.*', 'new_masterobat.*', 'stokreal.id as idx')->where('stokreal.flag', '')
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
            ->where('stokreal.kdruang', $kdruang)
            ->where(function ($x) {
                $x->where('stokreal.nopenerimaan', 'like', '%' . request('q') . '%')
                    ->orwhere('stokreal.kdobat', 'like', '%' . request('q') . '%')
                    ->orwhere('new_masterobat.nama_obat', 'like', '%' . request('q') . '%');
            })

            ->paginate(request('per_page'));
        return new JsonResponse($stokreal);
    }

    public static function updatestokdepo($request)
    {
        $kembalikan = Stokreal::select(
            'stokreal.nopenerimaan as nopenerimaan',
            'stokreal.kdobat as kdobat',
            'stokreal.harga as harga',
            DB::raw('stokreal.jumlah + retur_penjualan_r.jumlah_retur as masuk')
        )
            ->leftjoin('retur_penjualan_r', function ($e) {
                $e->on('retur_penjualan_r.nopenerimaan', 'stokreal.nopenerimaan')
                    ->on('retur_penjualan_r.kdobat', 'stokreal.kdobat')
                    ->on('retur_penjualan_r.harga_beli', 'stokreal.harga');
            })
            ->where('retur_penjualan_r.kdobat', $request->kdobat)
            ->where('stokreal.kdruang', $request->koderuang)
            ->where('retur_penjualan_r.noresep', $request->noresep)
            ->get();
        foreach ($kembalikan as $e) {
            $updatestok = Stokreal::where('nopenerimaan', $e->nopenerimaan)
                ->where('kdobat', $e->kdobat)
                ->where('stokreal.kdruang', $request->koderuang)
                ->where('harga', $e->harga)->first();
            $updatestok->jumlah = $e->masuk;
            $updatestok->save();
        }
        return 200;
    }
}
