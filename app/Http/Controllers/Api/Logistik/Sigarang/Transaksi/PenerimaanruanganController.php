<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\Penerimaanruangan\DetailsPenerimaanruangan;
use App\Models\Sigarang\Transaksi\Penerimaanruangan\Penerimaanruangan;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenerimaanruanganController extends Controller
{
    //
    public function index()
    { {
            $data = Permintaanruangan::where('status', '=', 7)
                ->with('details.barangrs.mapingbarang.barang108', 'details.satuan', 'pj', 'pengguna')->get();

            return new JsonResponse($data);
        }
    }
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            if ($request->has('details')) {
                $detail = $request->details;
                unset($request['details']);
            }
            // return new JsonResponse($detail);
            if ($request->has('permintaan_id')) {
                $permintaan_id = $request->permintaan_id;
                $permintaan = $this->updatePermintaan($permintaan_id);
                unset($request['permintaan_id']);
            }
            $penerimaan = Penerimaanruangan::updateOrCreate(['id' => $request->id], $request->all());
            if ($detail) {
                foreach ($detail as $key) {
                    $penerimaan->details()->updateOrCreate(['id' => $key['id']], $key);
                }
            }
            if ($penerimaan->wasRecentlyCreated) {
                $status = 201;
                $pesan = ['message' => 'Penerimaan Ruangan telah disimpan'];
            } else if ($penerimaan->wasChanged()) {
                $status = 200;
                $pesan = ['message' => 'Penerimaan Ruangan telah diupdate'];
            } else {
                $status = 500;
                $pesan = ['message' => 'Penerimaan Ruangan gagal dibuat'];
            }
            DB::commit();
            return new JsonResponse($pesan, $status);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }
    public function updatePermintaan($id)
    {
        $permintaan = Permintaanruangan::find($id);
        $permintaan->update([
            'status' => 8
        ]);
        if (!$permintaan->wasChanged()) {
            return false;
        }
        return true;
    }
    public function getItems()
    {
        $kode = request('kode_pengguna');
        // $data = DetailsPenerimaanruangan::distinct()->get(['kode_rs']);
        $data = DetailsPenerimaanruangan::selectRaw('kode_rs, sum(jumlah) as jml')
            ->whereHas('penerimaanruangan', function ($wew) use ($kode) {
                $wew->where('kode_pengguna', '=', $kode)
                    ->where('status', '=', 1);
            })->groupBy('kode_rs')->get();
        return new JsonResponse($data, 200);
    }
    public function getPj()
    {
        $data = Penerimaanruangan::select('kode_penanggungjawab')->with('pj')->distinct()->get();
        $collection = collect($data);
        $maping = $collection->map(function ($item, $key) {
            return $item['pj'];
        });

        return new JsonResponse($maping, 200);
    }
}
