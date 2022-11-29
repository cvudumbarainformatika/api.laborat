<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistribusiController extends Controller
{

    public function getPermintaanVerified()
    {
        $data = Permintaanruangan::where('status', '>=', 6)
            ->orderBy(request('order_by'), request('sort'))
            ->with('details.barangrs', 'details.barang108', 'details.satuan', 'pj', 'pengguna')
            ->filter(request(['q']))
            ->paginate(request('per-page'));
        // if (count($data)) {
        //     foreach ($data as $key) {
        //         $key->gudang = collect($key->details)->groupBy('dari');
        //     }
        // }
        $collection = collect($data);
        return new JsonResponse([
            'data' => $collection->only('data'),
            'meta' => $collection->except('data'),
        ], 200);
    }
}
