<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Anamnesis;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Anamnesis\Anamnesis as AnamnesisAnamnesis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnamnesisController extends Controller
{
    public function simpananamnesis(Request $request)
    {
        if ($request->has('id')) {
            $simpananamnesis = AnamnesisAnamnesis::where('id', $request->id)->update(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => date('Y-m-d H:i:s'),
                    'rs4' => $request->keluhanutama,
                    'riwayatpenyakit' => $request->riwayatpenyakit ?? '',
                    'riwayatalergi' => $request->riwayatalergi ?? '',
                    'keteranganalergi' => $request->keteranganalergi ?? '',
                    'riwayatpengobatan' => $request->riwayatpengobatan ?? '',
                    'user'  => auth()->user()->pegawai_id,
                ]
            );
        } else {
            $simpananamnesis = AnamnesisAnamnesis::create(
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
                    'user'  => auth()->user()->pegawai_id,
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

    public function hapusanamnesis(Request $request)
    {
        $cari = AnamnesisAnamnesis::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'MAAF DATA TIDAK DITEMUKAN'], 500);
        }
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 501);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }

    public function historyanamnesis()
    {
        $history = AnamnesisAnamnesis::select(
            'rs3 as tgl',
            'rs4 as keluhanutama',
            'riwayatpenyakit',
            'riwayatalergi',
            'keteranganalergi',
            'riwayatpengobatan'
        )
            ->where('rs2', request('norm'))
            ->orderBy('rs3', 'DESC')
            ->get();
        return new JsonResponse($history);
    }
}
