<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi;

use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Penunjang\Kamaroperasi\SurgicalSafety;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurgicalSafetyController extends Controller
{
    //
    public function getNakes()
    {
        $data = Petugas::select('id', 'nama', 'kdpegsimrs', 'kdgroupnakes')
            ->where('aktif', 'Aktif')
            ->whereIn('kdgroupnakes', ['1', '2'])
            ->get();
        return new JsonResponse([
            'data' => $data
        ]);
    }
    public function store(Request $request)
    {
        $data = SurgicalSafety::updateOrCreate(
            [
                'noreg' => $request->noreg,
                'nota' => $request->nota,
            ],
            $request->all()

        );


        return new JsonResponse([
            'message' => 'Data sudah disimpan',
            'req' => $request->all(),
            'data' => $data
        ]);
    }
}
