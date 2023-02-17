<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\MaxRuangan;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistribusiController extends Controller
{

    public function getPermintaanVerified()
    {
        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);
        $p = Permintaanruangan::query();
        if ($pegawai->role_id === 4) {
            $p->where('dari', $pegawai->kode_ruang);
        }
        $data = $p->where('status', '>=', 4)
            ->where('status', '<=', 7)
            ->orderBy(request('order_by'), request('sort'))
            ->with([
                // 'details.barangrs.mapingbarang.barang108', 'details.satuan',  'details.ruang',
                'pj', 'pengguna', 'details' => function ($wew) use ($pegawai) {
                    if ($pegawai->role_id === 4) {
                        $wew->where('dari', $pegawai->kode_ruang);
                    }
                    $wew->with('barangrs.mapingbarang.barang108', 'satuan', 'ruang');
                }
            ])
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        // ->get();

        foreach ($data as $key) {
            foreach ($key->details as $detail) {
                $temp = StockController::getDetailsStok($detail['kode_rs'], $detail['tujuan']);
                $max = MaxRuangan::where('kode_rs', $detail['kode_rs'])->where('kode_ruang', $detail['tujuan'])->first();
                $detail['barangrs']->maxStok = $max ? $max->max_stok : 0;
                $detail['barangrs']->alokasi = $temp ? $temp->alokasi : 0;
                $detail['temp'] = $temp;
                $detail['barangrs']->stokDepo = $temp ? $temp->stok : 0;
                $detail['barangrs']->stokRuangan = $temp ? $temp->stokRuangan : 0;
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
