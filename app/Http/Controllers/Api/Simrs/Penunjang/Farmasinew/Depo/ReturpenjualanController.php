<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturpenjualanController extends Controller
{
    public function caribynoresep()
    {
        $carinoresep = Resepkeluarheder::with(
            [
                'datapasien:rs1,rs2'
            ]
        )
            ->where('noresep', 'like', '%' . request('noresep') . '%')
            ->where('depo', request('kddepo'))
            //    ->where('flag', '4')
            ->get();
        return new JsonResponse(
            [
                'result' => $carinoresep
            ]
        );
    }
}
