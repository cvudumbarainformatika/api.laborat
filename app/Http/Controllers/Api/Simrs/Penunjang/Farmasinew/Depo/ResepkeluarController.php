<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Mjenisresep;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliH;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResepkeluarController extends Controller
{
    public function cekResepKeluar(Request $request)
    {
        $simpan = Resepkeluarheder::updateOrCreate(
            [
                'nota' => 'nonotaXXXXX'
            ],
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'tgl' => date('Y-m-d H:i:s'),
                'depo' => $request->kodedepo,
                'ruangan' => $request->kdruangan,
                'dokter' => $request->kddokter,
                'noresep' => $request->noresep,
                'sistembayar' => $request->sistembayar,
                'diagnosa' => $request->diagnosa,
                'kodeincbg' => $request->kodeincbg,
                'uraianinacbg' => $request->uraianinacbg,
                'tarifina' => $request->tarifina,
                'tagihanrs' => $request->tagihanrs,
            ]
        );
        return new JsonResponse(['simpan' => $simpan, 'message' => 'tak simpan headernya'], 410);
    }
    public function resepkeluar(Request $request)
    {

        $cekjumlahstok = Stokreal::select(DB::raw('sum(jumlah) as jumlahstok'))
            ->where('kdobat', $request->kodeobat)->where('kdruang', $request->kodedepo)
            ->where('jumlah', '!=', 0)
            ->orderBy('tglexp')
            ->get();

        if ($request->jumlah > $cekjumlahstok[0]->jumlahstok) {
            return new JsonResponse(['message' => 'Maaf Stok Tidak Mencukupi...!!!'], 500);
        }

        if ($request->kodedepo === 'Gd-04010102') {
            $procedure = 'resepkeluardeporanap(@nomor)';
            $colom = 'deporanap';
            $lebel = 'D-RI';
        } elseif ($request->kodedepo === 'Gd-04010103') {
            $procedure = 'resepkeluardepook(@nomor)';
            $colom = 'depook';
            $lebel = 'D-KO';
        } elseif ($request->kodedepo === 'Gd-05010101') {
            $procedure = 'resepkeluardeporajal(@nomor)';
            $colom = 'deporajal';
            $lebel = 'D-RJ';
        } else {
            $procedure = 'resepkeluardepoigd(@nomor)';
            $colom = 'depoigd';
            $lebel = 'D-IR';
        }

        if ($request->nota === '' || $request->nota === null) {
            DB::connection('farmasi')->select('call ' . $procedure);
            $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
            $wew = $x[0]->$colom;
            $nonota = FormatingHelper::penerimaanobat($wew, $lebel);
        } else {
            $nonota = $request->nota;
        }

        $user = FormatingHelper::session_user();
        // $simpanrinci = Resepkeluarrinci::create([
        //     'nota' => $nonota,
        // ]);
        // return new JsonResponse(['simpan' => $simpanrinci, 'message' => 'tak simpan headernya'], 410);
        $simpan = Resepkeluarheder::updateOrCreate(
            [
                'nota' => $nonota
            ],
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'tgl' => date('Y-m-d H:i:s'),
                'depo' => $request->kodedepo,
                'ruangan' => $request->kdruangan,
                'dokter' => $request->kddokter,
                'noresep' => $request->noresep,
                'sistembayar' => $request->sistembayar,
                'diagnosa' => $request->diagnosa,
                'kodeincbg' => $request->kodeincbg,
                'uraianinacbg' => $request->uraianinacbg,
                'tarifina' => $request->tarifina,
                'tagihanrs' => $request->tagihanrs,
            ]
        );

        if (!$simpan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        $gudang = ['Gd-05010100', 'Gd-03010100'];
        $cariharga = Stokreal::select(DB::raw('max(harga) as harga'))
            ->whereIn('kdruang', $gudang)
            ->where('kdobat', $request->kodeobat)
            ->orderBy('tglpenerimaan', 'desc')
            ->limit(5)
            ->get();
        $harga = $cariharga[0]->harga;

        $jmldiminta = $request->jumlah;
        $caristok = Stokreal::where('kdobat', $request->kodeobat)->where('kdruang', $request->kodedepo)
            ->where('jumlah', '!=', 0)
            ->orderBy('tglexp')
            ->get();

        $index = 0;
        $masuk = $jmldiminta;

        while ($masuk > 0) {
            $sisa = $caristok[$index]->jumlah;
            if ($request->groupsistembayar == 1) {
                if ($caristok[$index]->harga <= 50000) {
                    $hargajual = (int) $harga + (int) $harga * (int) 28 / (int) 100;
                } elseif ($caristok[$index]->harga > 50000 && $caristok[$index]->harga <= 250000) {
                    $hargajual = (int) $harga + ((int) $harga * (int) 26 / (int) 100);
                } elseif ($caristok[$index]->harga > 250000 && $caristok[$index]->harga <= 500000) {
                    $hargajual = (int) $harga + (int) $harga * (int) 21 / (int) 100;
                } elseif ($caristok[$index]->harga > 500000 && $caristok[$index]->harga <= 1000000) {
                    $hargajual = (int) $harga + (int) $harga * (int) 16 / (int)100;
                } elseif ($caristok[$index]->harga > 1000000 && $caristok[$index]->harga <= 5000000) {
                    $hargajual = (int) $harga + (int) $harga * (int) 11 /  (int)100;
                } elseif ($caristok[$index]->harga > 5000000 && $caristok[$index]->harga <= 10000000) {
                    $hargajual = (int) $harga + (int) $harga * (int) 9 / (int) 100;
                } elseif ($caristok[$index]->harga > 10000000) {
                    $hargajual = (int) $harga + (int) $harga * (int) 7 / (int) 100;
                }
            } else {
                $hargajual = (int) $harga + (int) $harga * (int) 25 / (int)100;
            }

            if ($sisa < $masuk) {
                $sisax = $masuk - $sisa;

                $simpanrinci = Resepkeluarrinci::create(
                    [
                        'noreg' => $request->noreg,
                        'nota' => $nonota,
                        'kdobat' => $request->kodeobat,
                        'kandungan' => $request->kandungan,
                        'fornas' => $request->fornas,
                        'forkit' => $request->forkit,
                        'generik' => $request->generik,
                        'kode108' => $request->kode108,
                        'uraian108' => $request->uraian108,
                        'kode50' => $request->kode50,
                        'uraian50' => $request->uraian50,
                        'nopenerimaan' => $caristok[$index]->nopenerimaan,
                        'jumlah' => $caristok[$index]->jumlah,
                        'harga_beli' => $caristok[$index]->harga,
                        'hpp' => $harga,
                        'harga_jual' => $hargajual,
                        'aturan' => $request->aturan,
                        'keterangan' => $request->keterangan ?? '',
                        'user' => $user['kodesimrs']
                    ]
                );

                Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                    ->where('kdobat', $caristok[$index]->kdobat)
                    ->where('kdruang', $request->kodedepo)
                    ->update(['jumlah' => 0]);

                $masuk = $sisax;
                $index = $index + 1;
                //return $jmldiminta;
            } else {
                $sisax = $sisa - $masuk;

                $simpanrinci = Resepkeluarrinci::create(
                    [
                        'noreg' => $request->noreg,
                        'nota' => $nonota,
                        'kdobat' => $request->kodeobat,
                        'kandungan' => $request->kandungan,
                        'fornas' => $request->fornas,
                        'forkit' => $request->forkit,
                        'generik' => $request->generik,
                        'kode108' => $request->kode108,
                        'uraian108' => $request->uraian108,
                        'kode50' => $request->kode50,
                        'uraian50' => $request->uraian50,
                        'nopenerimaan' => $caristok[$index]->nopenerimaan,
                        'jumlah' => $masuk,
                        'harga_beli' => $caristok[$index]->harga,
                        'hpp' => $harga,
                        'harga_jual' => $hargajual,
                        'aturan' => $request->aturan,
                        'keterangan' => $request->keterangan,
                        'user' => $user['kodesimrs']
                    ]
                );

                Stokreal::where('nopenerimaan', $caristok[$index]->nopenerimaan)
                    ->where('kdobat', $caristok[$index]->kdobat)
                    ->where('kdruang', $request->kodedepo)
                    ->update(['jumlah' => $sisax]);
                $masuk = 0;
            }
        }
        //return $harga;
        return new JsonResponse([
            'heder' => $simpan,
            'rinci' => $simpanrinci,
            'nota' => $nonota,
            'message' => 'Data Berhasil Disimpan...!!!'
        ], 200);
    }

    public function listresep()
    {
        $listresep = Resepkeluarheder::with(
            [
                'datapasien',
                'rincian',
                'rincian.mobat',
                'sistembayar'
            ]
        )
            ->where('depo', request('kddepo'))
            ->get();
        return new JsonResponse($listresep);
    }

    public function hapusobat(Request $request)
    {

        $kembalikan = Stokreal::select(
            'stokreal.nopenerimaan as nopenerimaan',
            'stokreal.kdobat as kdobat',
            'stokreal.harga as harga',
            DB::raw('stokreal.jumlah + resep_keluar_r.jumlah as masuk')
        )
            ->leftjoin('resep_keluar_r', function ($e) {
                $e->on('resep_keluar_r.nopenerimaan', 'stokreal.nopenerimaan')
                    ->on('resep_keluar_r.kdobat', 'stokreal.kdobat')
                    ->on('resep_keluar_r.harga_beli', 'stokreal.harga');
            })
            ->where('resep_keluar_r.kdobat', $request->kdobat)
            ->where('stokreal.kdruang', $request->koderuang)
            ->where('resep_keluar_r.nota', $request->nota)
            ->get();
        foreach ($kembalikan as $e) {
            $updatestok = Stokreal::where('nopenerimaan', $e->nopenerimaan)
                ->where('kdobat', $e->kdobat)
                ->where('stokreal.kdruang', $request->koderuang)
                ->where('harga', $e->harga)->first();
            $updatestok->jumlah = $e->masuk;
            $updatestok->save();

            $hapusobatx = Resepkeluarrinci::where('kdobat', $request->kdobat)->where('nota', $request->nota)->first();
            $hapusobatx->delete();
        }

        return new JsonResponse(['message' => 'Data Berhasil dihapus...!!!'], 200);
        // $hapusobat = Resepkeluarrinci::where('kdobat', $request->kdobat)->where('nota', $request->nota)->get();
        // foreach ($hapusobat as $x) {
        //     $hapusobatx = Resepkeluarrinci::where('kdobat', $request->kdobat)->where('nota', $request->nota)->first();
        //     $hapusobatx->delete();
        // }

        // $hapusobat->delete();
    }

    public function listjenisresep()
    {
        $listjenisresep = Mjenisresep::where('hidden', '')->get();
        return new JsonResponse($listjenisresep);
    }
}
