<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Pemesanan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliH;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliR;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DialogrencanapemesananController extends Controller
{
    public function dialogrencanabeli()
    {
        $rencanabeli = RencanabeliH::where('no_rencbeliobat', 'LIKE', '%' . request('no_rencbeliobat') . '%')
                        ->where('flag', '')
                        ->orderBy('tgl')->paginate(request('per_page'));
        return new JsonResponse($rencanabeli);
    }

    public function dialogrencanabeli_rinci()
    {
        $rencanabelirinci = RencanabeliR::with(['mobat'])->where('no_rencbeliobat', request('norencanabeliobat'))->get();
        return new JsonResponse($rencanabelirinci);
    }
}
