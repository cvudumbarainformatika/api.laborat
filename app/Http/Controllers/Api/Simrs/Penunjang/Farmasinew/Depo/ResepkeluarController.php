<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResepkeluarController extends Controller
{
    public function resepkeluar(Request $request)
    {
        if ($request->kodedepo === 'Gd-04010102') {
            $procedure = 'resepkeluardeporanap(@nomor)';
            $colom = 'deporanap';
            $lebel = 'D-RI';
        } elseif ($request->kodedepo === 'Gd-04010103') {
            $procedure = 'resepkeluardepook(@nomor)';
            $colom = 'depook';
            $lebel = 'D-KO';
        } elseif ($request->kodedepo === 'Gd-05010101') {
            $procedure = 'resepkeluardeporajal(@nomor)';
            $colom = 'deporajal';
            $lebel = 'D-RJ';
        } else {
            $procedure = 'resepkeluardepoigd(@nomor)';
            $colom = 'depoigd';
            $lebel = 'D-IR';
        }

        if ($request->nota === '' || $request->nota === null) {
            DB::connection('farmasi')->select('call ' . $procedure);
            $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
            $wew = $x[0]->$colom;
            $nonota = FormatingHelper::penerimaanobat($wew, $lebel);
        } else {
            $nonota = $request->nonota;
        }
        $user = FormatingHelper::session_user();
        $simpan = Resepkeluarheder::firstorcreate(
            [
                'nota' => $nonota
            ],
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'ruangan' => $request->kdruangan,
                'depo' => $request->kodedepo,
                'noreg' => $request->noreg,
                'noreg' => $request->noreg,
            ]
        );
    }
}
