<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\MaxRuangan;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistribusiController extends Controller
{

    public function getPermintaanVerified()
    {
        $data = Permintaanruangan::where('status', '>=', 4)
            ->where('status', '<=', 7)
            ->orderBy(request('order_by'), request('sort'))
            ->with('details.barangrs.mapingbarang.barang108',  'details.satuan', 'pj', 'pengguna')
            ->filter(request(['q']))
            ->paginate(request('per_page'));

        foreach ($data as $key) {
            foreach ($key->details as $detail) {
                $temp = StockController::getDetailsStok($detail['kode_rs'], $detail['tujuan']);
                $max = MaxRuangan::where('kode_rs', $detail['kode_rs'])->where('kode_ruang', $detail['tujuan'])->first();
                $detail['barangrs']->maxStok = $max->max_stok;
                $detail['barangrs']->alokasi = $temp->alokasi;
                $detail['temp'] = $temp;
                $detail['barangrs']->stokDepo = $temp->stok;
                $detail['barangrs']->stokRuangan = $temp->stokRuangan;
            }
        }

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

        // $permintaanruangan = Permintaanruangan::find($request->id);
        $permintaanruangan = Permintaanruangan::with('details')->find($request->id);
        $temp = PenerimaanruanganController::telahDiDistribusikan($request, $permintaanruangan);
        if ($temp['status'] !== 201) {
            return new JsonResponse($temp, $temp['status']);
        }
        try {

            DB::beginTransaction();

            $tanggal_distribusi = $request->tanggal !== null ? $request->tanggal : date('Y-m-d H:i:s');
            $status = 7;
            // $data = Permintaanruangan::find($request->id);
            $data = $permintaanruangan;
            $data->update([
                'no_distribusi' => $request->no_distribusi,
                'tanggal_distribusi' => $tanggal_distribusi,
                'status' => $status,
            ]);
            foreach ($data->details as $key) {
                // $data->details()->updateOrCreate(['id' => $key['id']], ['jumlah_distribusi' => $key['jumlah_distribusi']]);
                $data->details()->updateOrCreate(['id' => $key['id']], ['jumlah_distribusi' => $key['jumlah_disetujui']]);
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
            ], 410);
        }
    }
}
