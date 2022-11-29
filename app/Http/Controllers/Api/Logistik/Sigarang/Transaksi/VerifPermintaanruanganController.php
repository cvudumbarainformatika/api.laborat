<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifPermintaanruanganController extends Controller
{
    // ambil semua permintaan yang sudah selesai di input
    public function getPermintaan()
    {
        $data = Permintaanruangan::where('status', '=', 5)
            ->with('details.barangrs', 'details.satuan', 'pj', 'pengguna')->get();
        // if (count($data)) {
        //     foreach ($data as $key) {
        //         $key->gudang = collect($key->details)->groupBy('dari');
        //     }
        // }
        return new JsonResponse($data);
    }

    public function updatePermintaan(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'details' => 'required',
        ]);
        $details = $request->details;
        $permintaan = Permintaanruangan::updateOrCreate(['id' => $request->id], $request->only('status', 'tanggal_verif'));
        foreach ($details as $value) {
            $id = $value['id'];
            $permintaan->details()->updateOrCreate(['id' => $id], $value);
        }
        if (!$permintaan->wasChanged()) {
            return new JsonResponse(['message' => 'data gagal di update'], 501);
        }
        return new JsonResponse($permintaan, 200);
    }
}
