<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Uang;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PembayaranController extends Controller
{
    //
    public function cariBast()
    {
        $data = PenerimaanHeder::select('nobast')
            ->whereNotNull('tgl_bast')
            ->whereNull('tgl_pembayaran')
            ->distinct('nobast')
            ->orderBy('nobast')
            ->get();
        return new JsonResponse($data);
    }
    public function ambilBast()
    {
        $data = PenerimaanHeder::where('nobast', request('nobast'))
            ->with([
                'faktur',
                'penerimaanrinci:nopenerimaan,subtotal'
            ])
            ->get();
        return new JsonResponse($data);
    }
    public function listPembayaran()
    {
        return new JsonResponse(request()->all());
    }
    public function simpan(Request $request)
    {
        return new JsonResponse($request->all());
    }
}
