<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Cathlab;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Cathlab\TransCathlab;
use Illuminate\Http\Request;

class TransCatlabController extends Controller
{
    public function simpancathlab(Request $request)
    {
        $tindakan = $request->tindakan;
        $kd_tindakan = $tindakan->kode;
        return $kd_tindakan;
        $simpan = TransCathlab::updateOrCreate(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'nota' => $request->nota,
                'kd_tindakan' => $request->noreg,

            ],
            [
                'noreg' => $request->noreg,
                'noreg' => $request->noreg,
                'noreg' => $request->noreg,
                'noreg' => $request->noreg,
                'noreg' => $request->noreg,
                'noreg' => $request->noreg,
            ]
        );
    }
}
