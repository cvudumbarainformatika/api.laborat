<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepoController extends Controller
{
    public function lihatstokgudang()
    {

        $gudang = request('kdgudang');
        if ($gudang == '' || $gudang == null) {
            $stokgudang = Stokrel::select(
                'stokreal.*',
                'new_masterobat.*',
                DB::raw('sum(stokreal.jumlah) as  jumlah'),
                'new_masterobat.nama_obat as nama_obat'
            )->with([
                'permintaanobatrinci' => function ($permintaanobatrinci) {
                    $permintaanobatrinci->select(
                        'permintaan_r.kdobat',
                        DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                    )
                        ->leftjoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                        ->where('permintaan_h.flag', '');
                }
            ])
                ->join('new_masterobat', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
                ->where('stokreal.kdruang', $gudang)
                ->where('new_masterobat.nama_obat', 'Like', '%' . request('nama_obat') . '%')
                ->groupBy('stokreal.kdobat', 'stokreal.kdruang')
                ->get();
            $datastok = $stokgudang->map(function ($xxx) {
                $stolreal = $xxx->jumlah;
                $permintaantotal = count($xxx->permintaanobatrinci) > 0 ? $xxx->permintaanobatrinci[0]->allpermintaan : 0;
                $stokalokasi = (int) $stolreal - (int) $permintaantotal;
                $xxx['stokalokasi'] = $stokalokasi;
                return $xxx;
            });
            return new JsonResponse(
                ['obat' => $datastok]
            );
        } else {
            $stokgudang = Stokrel::select(
                'stokreal.*',
                'new_masterobat.*',
                DB::raw('sum(stokreal.jumlah) as  jumlah'),
                'new_masterobat.nama_obat as nama_obat'
            )->with([
                'permintaanobatrinci' => function ($permintaanobatrinci) {
                    $permintaanobatrinci->select(
                        'permintaan_r.kdobat',
                        DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                    )
                        ->leftjoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                        ->where('permintaan_h.flag', '');
                }
            ])
                ->join('new_masterobat', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
                ->where('stokreal.kdruang', $gudang)
                ->where('new_masterobat.nama_obat', 'Like', '%' . request('nama_obat') . '%')
                ->groupBy('stokreal.kdobat', 'stokreal.kdruang')
                ->get();
            $datastok = $stokgudang->map(function ($xxx) {
                $stolreal = $xxx->jumlah;
                $permintaantotal = count($xxx->permintaanobatrinci) > 0 ? $xxx->permintaanobatrinci[0]->allpermintaan : 0;
                $stokalokasi = (int) $stolreal - (int) $permintaantotal;
                $xxx['stokalokasi'] = $stokalokasi;
                return $xxx;
            });
            return new JsonResponse(
                ['obat' => $datastok]
            );
        }
    }

    public function simpanpermintaandepo(Request $request)
    {
        $stokreal = Stokrel::select('jumlah as stok')->where('kdobat', $request->kdobat)->where('kdruang', $request->tujuan)->first();
        $allpermintaan = Permintaandeporinci::select(DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan'))
            ->leftjoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
            ->where('permintaan_h.flag', '')->where('kdobat', $request->kdobat)->where('tujuan', $request->tujuan)
            ->groupby('kdobat')->get();
        $stokalokasi = (int) $stokreal->stok - (int) $allpermintaan[0]->allpermintaan;
        if ($request->jumlah_minta > $stokalokasi) {
            return new JsonResponse(['message' => 'Maaf Stok Alokasi Tidak mencukupi...!!!'], 500);
        }

        if ($request->nopermintaan === '' || $request->nopermintaan === null) {
            DB::connection('farmasi')->select('call permintaandepo(@nomor) ');
            $x = DB::connection('farmasi')->table('conter')->select('permintaandepo')->get();
            $wew = $x[0]->permintaandepo;

            $nopermintaandepo = FormatingHelper::permintaandepo($wew, 'REQ-DEPO');
            $simpanpermintaandepo = Permintaandepoheder::firstOrCreate(
                ['no_permintaan' => $nopermintaandepo],
                [
                    'tgl_permintaan' => date('Y-m-d H:i:s'),
                    'dari' => $request->dari,
                    'tujuan' => $request->tujuan,
                    'user' => auth()->user()->pegawai_id
                ]
            );
            if (!$simpanpermintaandepo) {
                return new JsonResponse(['message' => 'Permintaan Gagal Disimpan...!!!'], 500);
            }

            $simpanrincipermintaandepo = Permintaandeporinci::create(
                [
                    'no_permintaan' => $nopermintaandepo,
                    'kdobat' => $request->kdobat,
                    'stok_alokasi' => $request->stok_alokasi,
                    'mak_stok' => $request->mak_stok,
                    'jumlah_minta' => $request->jumlah_minta,
                    'status_obat' => $request->status_obat
                ]
            );

            if (!$simpanrincipermintaandepo) {
                return new JsonResponse(['message' => 'Permintaan Gagal Disimpan...!!!'], 500);
            }
            return new JsonResponse(
                [
                    'message' => 'Data Berhasil Disimpan...!!!',
                    'notrans' => $nopermintaandepo,
                    'heder' => $simpanpermintaandepo,
                    'rinci' => $simpanrincipermintaandepo
                ]
            );
        } else {
            $simpanrincipermintaandepo = Permintaandeporinci::create(
                [
                    'no_permintaan' => $request->no_permintaan,
                    'kdobat' => $request->kdobat,
                    'stok_alokasi' => $request->stok_alokasi,
                    'mak_stok' => $request->mak_stok,
                    'jumlah_minta' => $request->jumlah_minta,
                    'status_obat' => $request->status_obat
                ]
            );

            if (!$simpanrincipermintaandepo) {
                return new JsonResponse(['message' => 'Permintaan Gagal Disimpan...!!!'], 500);
            }
            return new JsonResponse(
                [
                    'message' => 'Data Berhasil Disimpan...!!!',
                    'notrans' => $request->no_permintaan,
                    'rinci' => $simpanrincipermintaandepo
                ]
            );
        }
    }

    public function kuncipermintaan(Request $request)
    {
        $kuncipermintaan = Permintaandepoheder::where('no_permintaan', $request->no_permintaan)->update(['flag' => '1']);
        if (!$kuncipermintaan) {
            return new JsonResponse(['message' => 'Maaf Permintaan Gagal Dikirim Ke Gudang,Moho Periksa Kembali Data Anda...!!!'], 500);
        }
        return new JsonResponse(['message' => 'Permintaan Berhasil Dikirim Kegudang...!!!'], 200);
    }

    public function listpermintaandepo()
    {
        $depo = request('kddepo');
        $nopermintaan = request('no_permintaan');
        if ($depo === '' || $depo === null) {
            $listpermintaandepo = Permintaandepoheder::with('permintaanrinci')
                ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
                ->orderBY('tgl_permintaan', 'desc')
                ->get();
            return new JsonResponse($listpermintaandepo);
        } else {

            $listpermintaandepo = Permintaandepoheder::with('permintaanrinci')
                ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
                ->where('dari', $depo)
                ->orderBY('tgl_permintaan', 'desc')
                ->get();
            return new JsonResponse($listpermintaandepo);
        }
    }
}
