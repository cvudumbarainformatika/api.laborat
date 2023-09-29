<?php

namespace App\Http\Controllers\Api\Simrs\Planing;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Planing\Mplaning;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Rajal\Listkonsulantarpoli;
use App\Models\Simrs\Rajal\WaktupulangPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaningController extends Controller
{
    public function mpalningrajal()
    {
        $mplanrajal = Mplaning::where('hidden', '!=', '1')->where('unit', 'RJ')->get();
        return new JsonResponse($mplanrajal);
    }

    public function simpanplaningpasien(Request $request)
    {
        if ($request->planing == 'Konsultasi') {
            $simpanplaningpasien = self::simpankonsulantarpoli($request);
            return ($simpanplaningpasien);
        }
    }

    public static function simpankonsulantarpoli($request)
    {
        $cek = Listkonsulantarpoli::where('noreg_lama', $request->noreg_lama)->where('flag', '')->count();
        if ($cek > 0) {
            return new JsonResponse(['message' => 'Maaf, Data Pasien Ini Masih Ada Dalam List Konsulan TPPRJ...!!!'], 500);
            //500;
        }
        $simpankonsulantarpoli = Listkonsulantarpoli::firstOrCreate(
            [
                'noreg_lama' => $request->noreg_lama
            ],
            [
                'norm' => $request->norm,
                'tgl_kunjungan' => $request->tgl_kunjungan,
                'tgl_rencana_konsul' => $request->tgl_rencana_konsul,
                'kdpoli_asal' => $request->kdpoli_asal,
                'kdpoli_tujuan' => $request->kdpoli_tujuan,
                'kddokter_asal' => $request->kddokter_asal
            ]
        );

        if (!$simpankonsulantarpoli) {
            return new JsonResponse(['message' => 'Maaf, Gagal Mengkonsulkan Pasien Ini, Cek Lagi Data Yang Dimasukkan...!!!'], 500);
        }

        $simpanakhir = WaktupulangPoli::firstOrCreate(
            [
                'rs1' => $request->noreg,
                'rs2' => $request->norm,
                'rs3' => $request->kdpoli_tujuan,
                'rs4' => $request->planing
            ]
        );

        if (!$simpanakhir) {
            return new JsonResponse(['message' => 'Maaf, Gagal Mengkonsulkan Pasien Ini, Cek Lagi Data Yang Dimasukkan...!!!'], 500);
        }

        $updatekunjungan = KunjunganPoli::where('rs1', $request->noreg)
            ->update(
                [
                    'rs19' => 1,
                    'rs24' => 1
                ]
            );
        if (!$updatekunjungan) {
            return new JsonResponse(['message' => 'Maaf, Gagal Mengkonsulkan Pasien Ini, Cek Lagi Data Yang Dimasukkan...!!!'], 500);
        }
        return new JsonResponse(['message' => 'Berhasil Mengirim Data Ke List Konsulan TPPRJ Pasien Ini...!!!'], 200);
    }
}
