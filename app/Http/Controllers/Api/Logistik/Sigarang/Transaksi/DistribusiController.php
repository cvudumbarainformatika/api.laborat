<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\MaxRuangan;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Transaksi\Permintaanruangan\DetailPermintaanruangan;
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
        // $data = $p->where('status', '>=', 4)
        //     ->where('status', '<=', 7)
        //     ->orderBy(request('order_by'), request('sort'))
        if (request('status') && request('status') !== null) {
            $p->where('status', '=', request('status'));
        } else {
            $p->where('status', '>=', 4)
                ->where('status', '<=', 7);
        }
        $data = $p->orderBy(request('order_by'), request('sort'))
            ->with([
                // 'details.barangrs.mapingbarang.barang108', 'details.satuan',  'details.ruang',
                'pj', 'pengguna', 'details' => function ($wew) use ($pegawai) {
                    if ($pegawai->role_id === 4) {
                        $wew->where('dari', $pegawai->kode_ruang);
                    }
                    $wew->with('barangrs.mapingbarang.barang108', 'satuan', 'ruang');
                }
            ])
            ->filter(request(['q', 'r']))
            ->paginate(request('per_page'));
        // ->get();

        foreach ($data as $key) {
            foreach ($key->details as $detail) {
                $temp = StockController::getDetailsStok($detail['kode_rs'], $detail['tujuan']);
                $max = MaxRuangan::where('kode_rs', $detail['kode_rs'])
                    ->where('kode_ruang', $detail['tujuan'])
                    ->first();
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

        $det = DetailPermintaanruangan::where('permintaanruangan_id', $request->id)->get();
        foreach ($det as $key => $detail) {

            if (!$detail['tujuan']) {
                return new JsonResponse(['message' => 'periksa data ruangan yang melakukan permintaan'], 422);
            }

            // check stok masih cukup atau tidak
            $dari = RecentStokUpdate::where('kode_ruang', $detail['dari'])
                ->where('kode_rs', $detail['kode_rs'])
                ->where('sisa_stok', '>', 0)
                ->with('barang')
                ->get();

            $sisaStok = collect($dari)->sum('sisa_stok');
            $disetujui = $detail['jumlah_disetujui'];

            if ($disetujui > 0) {
                if (count($dari) === 0) {
                    $barang = BarangRS::where('kode', $detail['kode_rs'])->first();
                    $pesan = 'stok ' .  $barang->nama . ' tidak ada';
                    $status = 410;

                    return new JsonResponse(['status' => $status, 'message' => $pesan,], 410);
                }

                if ($sisaStok < $disetujui) {
                    $barang = $dari[$key]['barang']['nama'];
                    $pesan = 'stok ' .  $barang . ' tidak mencukupi';
                    $status = 410;

                    return new JsonResponse(['status' => $status, 'message' => $pesan,], 410);
                }
            }
        }
        $permintaanruangan = Permintaanruangan::find($request->id);
        // $permintaanruangan = Permintaanruangan::with('details')->find($request->id);
        // return new JsonResponse($permintaanruangan);
        // $permintaanruangan = Permintaanruangan::find($request->id);
        try {

            DB::beginTransaction();
            $temp = PenerimaanruanganController::telahDiDistribusikan($request, $permintaanruangan);
            if ($temp['status'] !== 201) {
                return new JsonResponse($temp, $temp['status']);
            }

            $tanggal_distribusi = $request->tanggal !== null ? $request->tanggal : date('Y-m-d H:i:s');
            $status = 7;
            // $status = 8;
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
            ], 417);
        }
    }
}
