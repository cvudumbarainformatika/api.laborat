<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PemfakturanController extends Controller
{
    //
    public function getPenerimaanBelumAdaFaktur()
    {
        $data = PenerimaanHeder::where('jenis_penerimaan', 'Pesanan')
            ->where('jenissurat', '!=', 'Faktur')
            ->with([
                'penerimaanrinci.masterobat',
                'pihakketiga:kode,nama,alamat,telepon,npwp,cp',
                'gudang:kode,nama',
            ])
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
}
