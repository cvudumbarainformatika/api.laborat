<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KonsinyasiController extends Controller
{
    //
    public function getListPemakaianKonsinyasi()
    {
        $data = Mobatnew::select('kd_obat', 'nama_obat')
            ->whereHas('persiapanoperasirinci', function ($q) {
                $q->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                    ->where('persiapan_operasis.flag', '4');
            })
            ->with([
                'persiapanoperasirinci' => function ($q) {
                    $q->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                        ->where('persiapan_operasis.flag', '4');
                }
            ])
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
}
