<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepoController extends Controller
{
    public function lihatstokgudang()
    {

        $gudang = request('kdgudang');
        $depo = request('kddepo');
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
            },
            'minmax' => function ($mimnmax) use ($depo) {
                $mimnmax->select('kd_obat', 'kd_ruang', 'max')->when($depo, function ($xxx) use ($depo) {
                    $xxx->where('kd_ruang', $depo);
                });
            }
        ])
            ->join('new_masterobat', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
            ->when($gudang, function ($wew) use ($gudang) {
                $wew->where('stokreal.kdruang', $gudang);
            })
            ->where('new_masterobat.nama_obat', 'Like', '%' . request('nama_obat') . '%')
            ->where('stokreal.jumlah', '>', 0)
            ->groupBy('stokreal.kdobat', 'stokreal.kdruang')
            ->get();
        $datastok = $stokgudang->map(function ($xxx) {
            $stolreal = $xxx->jumlah;
            $permintaantotal = count($xxx->permintaanobatrinci) > 0 ? $xxx->permintaanobatrinci[0]->allpermintaan : 0;
            $stokalokasi = (int) $stolreal - (int) $permintaantotal;
            $xxx['stokalokasi'] = $stokalokasi;
            return $xxx;
        });

        $stokdewe = Stokrel::select('kdobat', DB::raw('sum(stokreal.jumlah) as  jumlah'), 'kdruang')
            ->when($depo, function ($wew) use ($depo) {
                $wew->where('stokreal.kdruang', $depo);
            })
            ->where('jumlah', '>', 0)
            ->groupBy('stokreal.kdobat', 'stokreal.kdruang')
            ->get();

        return new JsonResponse(
            [
                'obat' => $datastok,
                'stokdewe' => $stokdewe
            ]
        );
    }

    public function simpanpermintaandepo(Request $request)
    {
        $cek = Permintaandepoheder::where('flag', '!=', '')->where('no_permintaan', $request->no_permintaan)->count();
        if ($cek > 0) {
            return new JsonResponse(['message' => 'Maaf Data ini Sudah Dikunci...!!!'], 500);
        }
        $stokreal = Stokrel::select('jumlah as stok')->where('kdobat', $request->kdobat)->where('kdruang', $request->tujuan)->first();
        $stokrealx = (int) $stokreal->stok;
        $allpermintaan = Permintaandeporinci::select(DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan'))
            ->leftjoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
            ->where('permintaan_h.flag', '')->where('kdobat', $request->kdobat)->where('tujuan', $request->tujuan)
            ->groupby('kdobat')->get();
        $allpermintaanx =  $allpermintaan[0]->allpermintaan ?? '';
        $stokalokasi = $stokrealx - (int) $allpermintaanx;

        if ($request->jumlah_minta > $stokalokasi) {
            return new JsonResponse(['message' => 'Maaf Stok Alokasi Tidak mencukupi...!!!'], 500);
        }

        if ($request->no_permintaan === '' || $request->no_permintaan === null) {
            DB::connection('farmasi')->select('call permintaandepo(@nomor) ');
            $x = DB::connection('farmasi')->table('conter')->select('permintaandepo')->get();
            $wew = $x[0]->permintaandepo;
            $nopermintaandepo = FormatingHelper::permintaandepo($wew, 'REQ-DEPO');
        } else {
            $nopermintaandepo = $request->no_permintaan;
        }

        $simpanpermintaandepo = Permintaandepoheder::updateorcreate(
            [
                'no_permintaan' => $nopermintaandepo,
            ],
            [
                'tgl_permintaan' => $request->tgl_permintaan ?? date('Y-m-d H:i:s'),
                'dari' => $request->dari,
                'tujuan' => $request->tujuan,
                'user' => auth()->user()->pegawai_id
            ]
        );
        if (!$simpanpermintaandepo) {
            return new JsonResponse(['message' => 'Permintaan Gagal Disimpan...!!!'], 500);
        }

        $simpanrincipermintaandepo = Permintaandeporinci::updateorcreate(
            [
                'no_permintaan' => $nopermintaandepo,
                'kdobat' => $request->kdobat
            ],
            [
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
                'rinci' => $simpanrincipermintaandepo,
                'stokalokasi' => $stokalokasi
            ]
        );
    }

    public function kuncipermintaan(Request $request)
    {
        $kuncipermintaan = Permintaandepoheder::where('no_permintaan', $request->no_permintaan)->first();
        $kuncipermintaan->flag = '1';
        $kuncipermintaan->tgl_kirim = date('Y-m-d H:i:s');
        $kuncipermintaan->save();

        return new JsonResponse(['message' => 'Permintaan Berhasil Dikirim Kegudang...!!!'], 200);
    }

    public function listpermintaandepo()
    {
        $depo = request('kddepo');
        $nopermintaan = request('no_permintaan');

        $listpermintaandepo = Permintaandepoheder::with('permintaanrinci.masterobat', 'asal:kode,nama', 'menuju:kode,nama')
            ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
            ->where('dari', 'like', '%' . $depo . '%')
            ->orderBY('tgl_permintaan', 'desc')
            ->get();
        return new JsonResponse($listpermintaandepo);
        // }
    }

    public function terimadistribusi(Request $request)
    {
        $obatditerima = Mutasigudangkedepo::select(
            'mutasi_gudangdepo.no_permintaan as nopermintaan',
            'mutasi_gudangdepo.nopenerimaan as nopenerimaan',
            'mutasi_gudangdepo.kd_obat as kodeobat',
            'mutasi_gudangdepo.jml as jml',
            'stokreal.tglpenerimaan as tglpenerimaan',
            'stokreal.harga as harga',
            'stokreal.tglexp as tglexp',
            'stokreal.nobatch as nobatch',
            'stokreal.nodistribusi as nodistribusi',
            'stokreal.jumlah',
            DB::raw('(stokreal.jumlah-mutasi_gudangdepo.jml) as sisa')
        )
            ->leftjoin('stokreal', function ($x) {
                $x->on('mutasi_gudangdepo.nopenerimaan', '=', 'stokreal.nopenerimaan')
                    ->on('mutasi_gudangdepo.kd_obat', '=', 'stokreal.kdobat');
            })
            ->where('no_permintaan', $request->no_permintaan)
            ->where('kdruang', $request->kdruang)
            ->orderBy('stokreal.tglexp')
            ->get();
        foreach ($obatditerima as $wew) {

            Stokreal::create(
                [
                    'nopenerimaan' => $wew->nopenerimaan,
                    'tglpenerimaan' => $wew->tglpenerimaan,
                    'kdobat' => $wew->kodeobat,
                    'jumlah' => $wew->jml,
                    'kdruang' => $request->tujuan,
                    'harga' => $wew->harga,
                    'tglexp' => $wew->tglexp,
                    'nobatch' => $wew->nobatch,
                    'nodistribusi' => $request->no_permintaan
                ]
            );
        }

        $user = FormatingHelper::session_user();
        $kuncipermintaan = Permintaandepoheder::where('no_permintaan', $request->no_permintaan)->first();
        $kuncipermintaan->flag = '4';
        $kuncipermintaan->tgl_terima_depo = date('Y-m-d H:i:s');
        $kuncipermintaan->user_terima_depo = $user['kodesimrs'];
        $kuncipermintaan->save();

        return new JsonResponse(['message' => 'Permintaan Berhasil Diterima & Masuk Ke stok...!!!'], 200);
    }
}
