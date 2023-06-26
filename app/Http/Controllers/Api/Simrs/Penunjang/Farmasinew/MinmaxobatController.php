<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mobat;
use App\Models\Simrs\Master\Mruangan;
use App\Models\Simrs\Penunjang\Farmasinew\Mminmaxobat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MinmaxobatController extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = Mminmaxobat::updateOrCreate(
            ['kd_obat' => $request->kd_obat, 'kd_ruang' => $request->kd_ruang],
            [
                'min' => $request->min,
                'max' => $request->max
            ]
        );

        if (!$simpan) {
            return new JsonResponse(['message' => 'DATA TIDAK TERSIMPAN...!!!'], 500);
        }
        return new JsonResponse(['message' => 'DATA TERSIMPAN...!!!'], 200);
    }

    public function caribynamaobat()
    {
        $id = Mruangan::where('uraian', 'LIKE', '%' . request('r') . '%')->pluck('kode');
        $qwerty = Mminmaxobat::with(['obat:kd_obat,nama_obat as namaobat', 'ruanganx:kode,uraian as namaruangan'])
            ->whereHas('obat', function ($e) {
                $e->where('new_masterobat.nama_obat', 'LIKE', '%' . request('o') . '%');
            })
            ->whereIn('kd_ruang', $id)
            ->paginate(request('per_page'));
        return new JsonResponse($qwerty, 200);
    }
}
