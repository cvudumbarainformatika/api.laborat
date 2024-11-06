<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Anamnesis\HistoryKehamilan;
use App\Models\Simrs\Anamnesis\HistoryPerkawinan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnamnesisKebidananController extends Controller
{
    public function simpanHistoryPerkawiananPasien(Request $request)
    {
        $simpan = HistoryPerkawinan::create(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'suami_ke' => $request->suamike,
                'lamapernikahan' => $request->lamapernikahan
            ]
        );
        return new JsonResponse(['message' => 'Data Sudah Tersimpan...!!!','result' => $simpan], 200);
    }

    public function hapusHistoryPerkawiananPasien(Request $request)
    {

        $simpan = HistoryPerkawinan::where('id', $request->id);
        $hapus = $simpan->delete();
        return new JsonResponse(['message' => 'Data Sudah Terhapus...!!!','result' => $hapus], 200);
    }

    public function simpanHistoryKehamilan(Request $request)
    {
        $simpan = HistoryKehamilan::create(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'tanggal_partus' => $request->tanggal,
                'tempat' => $request->tempat,
                'umurkehamilan' => $request->umurkehamilan,
                'jenispersalinan' => $request->jenispersalinan,
                'penolong' => $request->penolong,
                'penyulit' => $request->penyulit,
                'jeniskelamin' => $request->jk,
                'beratbadan' => $request->bb,
                'pb' => $request->pb,
                'nifas' => $request->nifas,
                //'user' => $request->norm,
            ]
        );
        return new JsonResponse(['message' => 'Data Sudah Tersimpan...!!!','result' => $simpan], 200);
    }

    public function hapusHistoryKehamilan(Request $request)
    {

        $simpan = HistoryKehamilan::where('id', $request->id);
        $hapus = $simpan->delete();
        return new JsonResponse(['message' => 'Data Sudah Terhapus...!!!','result' => $hapus], 200);
    }
}
