<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifPermintaanruanganController extends Controller
{
    // ambil semua permintaan yang sudah selesai di input
    public function getPerrmintaan()
    {
        $data = Permintaanruangan::where('status', '=', 2)
            ->with('details', 'pj', 'pengguna')->get();
        if (count($data)) {
            foreach ($data as $key) {
                $key->gudang = collect($key->details)->groupBy('dari');
            }
        }
        return new JsonResponse($data);
    }
}
