<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistribusiController extends Controller
{

    public function getPermintaanVerified()
    {
        $data = Permintaanruangan::where('status', '>=', 6)
            ->orderBy(request('order_by'), request('sort'))
            ->with('details.barangrs.mapingbarang.barang108',  'details.satuan', 'pj', 'pengguna')
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
    public function updateDistribusi(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'no_distribusi' => 'required',
        ]);

        $permintaanruangan = Permintaanruangan::find($request->id);
        $temp = PenerimaanruanganController::telahDiDistribusikan($request, $permintaanruangan);
        if ($temp['status'] !== 201) {
            return new JsonResponse($temp, $temp['status']);
        }
        try {

            DB::beginTransaction();

            $tanggal_distribusi = date('Y-m-d H:i:s');
            $status = 7;
            $data = Permintaanruangan::find($request->id);
            $data->update([
                'no_distribusi' => $request->no_distribusi,
                'tanggal_distribusi' => $tanggal_distribusi,
                'status' => $status,
            ]);
            foreach ($request->detail as $key) {
                $data->details()->updateOrCreate(['id' => $key['id']], ['jumlah_distribusi' => $key['jumlah_distribusi']]);
            }

            DB::commit();

            if (!$data->wasChanged()) {
                return new JsonResponse(['message' => 'data gagal di update'], 501);
            }
            return new JsonResponse(['message' => 'data berhasi di update'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }
}
