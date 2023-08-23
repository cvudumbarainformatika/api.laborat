<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Pemesanan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananRinci;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliH;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliR;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemesananController extends Controller
{
    public function simpan(Request $request)
    {
        if ($request->nopemesanan === '' || $request->nopemesanan === null) {
            if ($request->jumlahdpesan > $request->jumlah_bisa_dibeli) {
                return new JsonResponse(['message' => 'MAAF JUMLAH PESANAN LEBIH DARI JUMLAH YANG BISA DIBELI']);
                $cekjumlaha = PemesananRinci::select('jumlahdpesan')->where('noperencanaan', $request->noperencanaan)
                    ->where('kdobat', $request->kdobat)
                    ->sum('jumlahdpesan');
                $jumlaha = $cekjumlaha + $request->jumlahdpesan;
                if ($jumlaha > $request->jumlah_bisa_dibeli) {
                    return new JsonResponse(['message' => 'MAAF JUMLAH PESANAN LEBIH DARI JUMLAH YANG BISA DIBELI'], 500);
                }
            } else if ($request->jumlahdpesan === $request->jumlah_bisa_dibeli) {
                $cekjumlaha = PemesananRinci::select('jumlahdpesan')->where('noperencanaan', $request->noperencanaan)
                    ->where('kdobat', $request->kdobat)
                    ->sum('jumlahdpesan');
                $jumlaha = $cekjumlaha + $request->jumlahdpesan;
                if ($jumlaha > $request->jumlah_bisa_dibeli) {
                    return new JsonResponse(['message' => 'MAAF JUMLAH PESANAN LEBIH DARI JUMLAH YANG BISA DIBELI'], 500);
                }

                DB::connection('farmasi')->select('call pemesanan_obat(@nomor)');
                $x = DB::connection('farmasi')->table('conter')->select('pemesanan')->get();
                $wew = $x[0]->pemesanan;
                $nopemesanan = FormatingHelper::pemesananobat($wew, 'PES-BOBAT');

                $simpanheder = PemesananHeder::create([
                    'nopemesanan' => $nopemesanan,
                    'tgl_pemesanan' => date('Y-m-d H:i:s'),
                    'kdpbf' => $request->kdpbf,
                    'user' => auth()->user()->pegawai_id
                ]);

                if (!$simpanheder) {
                    return new JsonResponse(['message' => 'not ok'], 500);
                }

                $simpanrinci = PemesananRinci::create([
                    'nopemesanan' => $nopemesanan,
                    'noperencanaan' => $request->noperencanaan,
                    'kdobat'  => $request->kdobat,
                    'stok_real_gudang'  => $request->stok_real_gudang,
                    'stok_real_rs'  => $request->stok_real_rs,
                    'stok_max_rs'  => $request->stok_max_rs,
                    'jumlah_bisa_dibeli'  => $request->jumlah_bisa_dibeli,
                    'tgl_stok'  => $request->tgl_stok,
                    'jumlahdpesan'  => $request->jumlahdpesan,
                    'user'  => auth()->user()->pegawai_id,
                ]);

                if (!$simpanrinci) {
                    return new JsonResponse(['message' => 'not ok'], 500);
                }

                $wew = RencanabeliR::where('no_rencbeliobat', $request->noperencanaan)->where('kdobat', $request->kdobat)
                    ->update(['flag' => '1']);

                return new JsonResponse(
                    [
                        'message' => 'ok',
                        'notrans' => $nopemesanan,
                        'heder' => $simpanheder,
                        'rinci' => $simpanrinci
                    ],
                    200
                );
            } else {
                $cekjumlaha = PemesananRinci::select('jumlahdpesan')->where('noperencanaan', $request->noperencanaan)
                    ->where('kdobat', $request->kdobat)
                    ->sum('jumlahdpesan');
                $jumlaha = $cekjumlaha + $request->jumlahdpesan;
                if ($jumlaha > $request->jumlah_bisa_dibeli) {
                    return new JsonResponse(['message' => 'MAAF JUMLAH PESANAN LEBIH DARI JUMLAH YANG BISA DIBELI'], 500);
                }
                DB::connection('farmasi')->select('call pemesanan_obat(@nomor)');
                $x = DB::connection('farmasi')->table('conter')->select('pemesanan')->get();
                $wew = $x[0]->pemesanan;
                $nopemesanan = FormatingHelper::pemesananobat($wew, 'PES-BOBAT');

                $simpanheder = PemesananHeder::create([
                    'nopemesanan' => $nopemesanan,
                    'tgl_pemesanan' => date('Y-m-d H:i:s'),
                    'kdpbf' => $request->kdpbf,
                    'user' => auth()->user()->pegawai_id
                ]);

                if (!$simpanheder) {
                    return new JsonResponse(['message' => 'not ok'], 500);
                }

                $simpanrinci = PemesananRinci::create([
                    'nopemesanan' => $nopemesanan,
                    'noperencanaan' => $request->noperencanaan,
                    'kdobat'  => $request->kdobat,
                    'stok_real_gudang'  => $request->stok_real_gudang,
                    'stok_real_rs'  => $request->stok_real_rs,
                    'stok_max_rs'  => $request->stok_max_rs,
                    'jumlah_bisa_dibeli'  => $request->jumlah_bisa_dibeli,
                    'tgl_stok'  => $request->tgl_stok,
                    'jumlahdpesan'  => $request->jumlahdpesan,
                    'user'  => auth()->user()->pegawai_id,
                ]);

                if (!$simpanrinci) {
                    return new JsonResponse(['message' => 'not ok'], 500);
                }

                return new JsonResponse(
                    [
                        'message' => 'ok',
                        'notrans' => $nopemesanan,
                        'heder' => $simpanheder,
                        'rinci' => $simpanrinci
                    ],
                    200
                );
            }
        } else {
            $cekjumlaha = PemesananRinci::select('jumlahdpesan')->where('noperencanaan', $request->noperencanaan)
                ->where('kdobat', $request->kdobat)
                ->sum('jumlahdpesan');
            $jumlaha = $cekjumlaha + $request->jumlahdpesan;

            if ($jumlaha > $request->jumlah_bisa_dibeli) {
                return new JsonResponse(['message' => 'MAAF JUMLAH PESANAN LEBIH DARI JUMLAH YANG BISA DIBELI'], 500);
            } else if ($jumlaha == $request->jumlah_bisa_dibeli) {
                $simpanrinci = PemesananRinci::create([
                    'nopemesanan' => $request->nopemesanan,
                    'noperencanaan' => $request->noperencanaan,
                    'kdobat'  => $request->kdobat,
                    'stok_real_gudang'  => $request->stok_real_gudang,
                    'stok_real_rs'  => $request->stok_real_rs,
                    'stok_max_rs'  => $request->stok_max_rs,
                    'jumlah_bisa_dibeli'  => $request->jumlah_bisa_dibeli,
                    'tgl_stok'  => $request->tgl_stok,
                    'jumlahdpesan'  => $request->jumlahdpesan,
                    'user'  => auth()->user()->pegawai_id,
                ]);

                if (!$simpanrinci) {
                    return new JsonResponse(['message' => 'not ok'], 500);
                }

                $wew = RencanabeliR::where('no_rencbeliobat', $request->noperencanaan)->where('kdobat', $request->kdobat)
                    ->update(['flag' => '1']);

                return new JsonResponse(
                    [
                        'message' => 'ok',
                        'notrans' => $request->nopemesanan,
                        //    'heder' => $simpanheder,
                        'rinci' => $simpanrinci
                    ],
                    200
                );
            } else {
                $simpanrinci = PemesananRinci::create([
                    'nopemesanan' => $request->nopemesanan,
                    'noperencanaan' => $request->noperencanaan,
                    'kdobat'  => $request->kdobat,
                    'stok_real_gudang'  => $request->stok_real_gudang,
                    'stok_real_rs'  => $request->stok_real_rs,
                    'stok_max_rs'  => $request->stok_max_rs,
                    'jumlah_bisa_dibeli'  => $request->jumlah_bisa_dibeli,
                    'tgl_stok'  => $request->tgl_stok,
                    'jumlahdpesan'  => $request->jumlahdpesan,
                    'user'  => auth()->user()->pegawai_id,
                ]);

                if (!$simpanrinci) {
                    return new JsonResponse(['message' => 'not ok'], 500);
                }

                return new JsonResponse(
                    [
                        'message' => 'ok',
                        'notrans' => $request->nopemesanan,
                        //    'heder' => $simpanheder,
                        'rinci' => $simpanrinci
                    ],
                    200
                );
            }
        }
        // DB::connection('farmasi')->select('call pemesanan_obat(@nomor)');
        // $x = DB::connection('farmasi')->table('conter')->select('pemesanan')->get();
        // $wew = $x[0]->pemesanan;
        // $nopemesanan = FormatingHelper::pemesananobat($wew, 'PES-BOBAT');

        // $simpanpemesananheder = RencanabeliH::where('no_rencbeliobat', $request->no_rencbeliobat)->update([
        //     'nopemesanan' => $nopemesanan,
        //     'tglpemesanan' => date('Y-m-d H:i:s'),
        //     'kodepbf' => $request->kodepbf,
        //     'userpemesanan' => auth()->user()->pegawai_id
        // ]);

        // if(!$simpanpemesananheder)
        // {
        //     return new JsonResponse(['message' => 'GAGAL DISIMPAN'], 500);
        // }
        // $simpanpenerimaanrinci = RencanabeliR::where('no_rencbeliobat', $request->no_rencbeliobat)->update([
        //     'nopemesanan' => $nopemesanan,
        // ]);

        // if(!$simpanpenerimaanrinci)
        // {
        //     return new JsonResponse(['message' => 'GAGAL DISIMPAN'], 500);
        // }
        // return new JsonResponse(['message' => 'OK', $nopemesanan], 200);
    }

    public function listpemesanan()
    {
        // $listpemesanan = RencanabeliH::select('nopemesanan', 'tglpemesanan', 'kodepbf')
        //     ->with('pihakketiga')
        //     ->where('nopemesanan', '!=', '')
        //     ->orderBy('tglpemesanan')->paginate(request('per_page'));

        $listpemesanan = PemesananHeder::select('nopemesanan', 'tgl_pemesanan', 'kdpbf', 'flag')
            ->with(
                'pihakketiga',
                'rinci',
                'rinci.masterobat:kd_obat,nama_obat,merk,satuan_b,satuan_k,kandungan,bentuk_sediaan,kekuatan_dosis,volumesediaan,kelas_terapi'
            )
            ->orderBy('tgl_pemesanan')->paginate(request('per_page'));
        return new JsonResponse($listpemesanan);
    }

    public function kuncipemesanan(Request $request)
    {
        $kuncipemesanan = PemesananHeder::where('nopemesanan', $request->nopemesanan)->update(['flag' => '1']);
        if (!$kuncipemesanan) {
            return new JsonResponse(['message' => 'not ok'], 500);
        }
        return new JsonResponse(['message' => 'ok'], 200);
    }
}
