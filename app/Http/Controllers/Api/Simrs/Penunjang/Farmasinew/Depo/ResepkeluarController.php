<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResepkeluarController extends Controller
{
    public function resepkeluar(Request $request)
    {
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
            $nonota = $request->nonota;
        }
        $user = FormatingHelper::session_user();
        $simpan = Resepkeluarheder::firstorcreate(
            [
                'nota' => $nonota
            ],
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'tgl' => date('Y-m-d H:i:s'),
                'depo' => $request->kodedepo,
                'ruangan' => $request->kdruangan,
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

        // if (!$simpanrinci) {
        //     return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        // }

        $jmldiminta = $request->jumlah;
        $caristok = Stokreal::where('kdobat', $request->kodeobat)->where('kdruang', $request->kodedepo)
            ->where('jumlah', '!=', 0)
            ->orderBy('tglexp')
            ->get();
        $index = 0;
        $masuk = $jmldiminta;

        while ($masuk > 0) {
            $sisa = $caristok[$index]->jumlah;

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
                        'harga' => $caristok[$index]->harga,
                        'aturan' => $request->aturan,
                        'keterangan' => $request->keterangan,
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
                        'harga' => $caristok[$index]->harga,
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
        return new JsonResponse([
            'heder' => $simpan,
            'rinci' => $simpanrinci,
            'message' => 'Data Berhasil Disimpan...!!!'
        ], 200);
    }
}
