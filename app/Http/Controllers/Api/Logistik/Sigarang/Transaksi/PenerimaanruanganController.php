<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PenerimaanruanganController extends Controller
{
    //
    public function index()
    { {
            $data = Permintaanruangan::where('status', '=', 7)
                ->with('details.barangrs.mapingbarang.barang108', 'details.satuan', 'pj', 'pengguna')->get();
            // if (count($data)) {
            //     foreach ($data as $key) {
            //         $key->gudang = collect($key->details)->groupBy('dari');
            //     }
            // }
            return new JsonResponse($data);
        }
    }
}
