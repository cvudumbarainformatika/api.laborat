<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Pemakaian;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PemakaianObatController extends Controller
{
    function getPemakaianObat(){
        $obat=Mobatnew::where('nama_obat','LIKE','%'.request('q').'%')
        ->paginate(request('per_page'));
        $data=collect($obat)['data'];
        $meta=collect($obat)->except('data');
        return new JsonResponse([
            'data'=>$data,
            'meta'=>$meta,
            'req'=>request()->all(),
        ]);
    }
}
