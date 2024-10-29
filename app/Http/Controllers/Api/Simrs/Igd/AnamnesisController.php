<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Anamnesis\Anamnesis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnamnesisController extends Controller
{
    public function simpananamnesis(Request $request)
    {
        return ('wew');
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;

        if ($request->has('id')) {
            $hasil = Anamnesis::where('id', $request->id)->update(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => date('Y-m-d H:i:s'),
                    'rs4' => $request->keluhanutama,
                    'riwayatpenyakit' => $request->riwayatpenyakit ?? '',
                    'riwayatalergi' => $request->riwayatalergi ?? '',
                    'keteranganalergi' => $request->keteranganalergi ?? '',
                    'riwayatpengobatan' => $request->riwayatpengobatan ?? '',
                    'riwayatpenyakitsekarang' => $request->riwayatpenyakitsekarang ?? '',
                    'riwayatpenyakitkeluarga' => $request->riwayatpenyakitkeluarga ?? '',
                    'skreeninggizi' => $request->skreeninggizi ?? 0,
                    'asupanmakan' => $request->asupanmakan ?? 0,
                    'kondisikhusus' => $request->kondisikhusus ?? '',
                    'skor' => $request->skor ?? 0,
                    'scorenyeri' => $request->skornyeri ?? 0,
                    'keteranganscorenyeri' => $request->keteranganscorenyeri ?? '',
                    //    'keteranganscorenyeri' => $request->riwayatpekerjaan ?? '',
                    'user'  => $kdpegsimrs,
                ]
            );
            if ($hasil === 1) {
                $simpananamnesis = Anamnesis::where('id', $request->id)->first();
            } else {
                $simpananamnesis = null;
            }
        } else {
            $simpananamnesis = Anamnesis::create(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => date('Y-m-d H:i:s'),
                    'rs4' => $request->keluhanutama,
                    'riwayatpenyakit' => $request->riwayatpenyakit ?? '',
                    'riwayatalergi' => $request->riwayatalergi ?? '',
                    'keteranganalergi' => $request->keteranganalergi ?? '',
                    'riwayatpengobatan' => $request->riwayatpengobatan ?? '',
                    'riwayatpenyakitsekarang' => $request->riwayatpenyakitsekarang ?? '',
                    'riwayatpenyakitkeluarga' => $request->riwayatpenyakitkeluarga ?? '',
                    'skreeninggizi' => $request->skreeninggizi ?? 0,
                    'asupanmakan' => $request->asupanmakan ?? 0,
                    'kondisikhusus' => $request->kondisikhusus ?? '',
                    'skor' => $request->skor ?? 0,
                    'scorenyeri' => $request->skornyeri ?? 0,
                    'keteranganscorenyeri' => $request->keteranganscorenyeri ?? '',
                    'user'  => $kdpegsimrs,
                ]
            );
        }
        if (!$simpananamnesis) {
            return new JsonResponse(['message' => 'GAGAL DISIMPAN'], 500);
        }
        return new JsonResponse([
            'message' => 'BERHASIL DISIMPAN',
            'result' => $simpananamnesis
        ], 200);
    }
}
