<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\Transaksi\PemesananResource;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\Transaksi\Pemesanan\DetailPemesanan;
use App\Models\Sigarang\Transaksi\Pemesanan\Pemesanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PemesananController extends Controller
{
    //

    public function draft()
    {
        $complete = Pemesanan::where('reff', '=', request()->reff)
            ->where('status', '=', 2)->get();
        $draft = Pemesanan::where('reff', '=', request()->reff)
            ->where('status', '=', 1)
            ->latest('id')->with([
                'details.barang108', 'details.barangrs', 'details.satuan',
                'details_kontrak' => function ($kueri) {
                    $kueri->where('kunci', '=', 1)
                        ->where('flag', '=', '');
                }
            ])->get();
        if (count($complete)) {
            return new JsonResponse(['message' => 'completed']);
        }
        return PemesananResource::collection($draft);
    }
    public function adaPenerimaan()
    {
        $data = Pemesanan::where('status', '>=', 3)
            ->latest('id')->get();
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $second = $request->all();
        $second['tanggal'] = $request->tanggal !== null ? $request->tanggal : date('Y-m-d H:i:s');
        // unset($second['reff']);
        $anu = Pemesanan::where('nomor', $request->nomor)->first();
        // if ($anu) {
        //     return new JsonResponse($anu, 410);
        // }
        $valid = Validator::make($request->all(), [
            'reff' => 'required|min:5',
            'nomor' => [
                'required',
                Rule::when(($anu && $anu->reff !== $request->reff), ['unique:sigarang.pemesanans,nomor'])
            ]
        ]);
        if ($valid->fails()) {
            return new JsonResponse($valid->errors(), 422);
        }
        try {

            DB::beginTransaction();


            $data = Pemesanan::updateOrCreate(['reff' => $request->reff], $second);
            if ($request->has('kode_rs') && $request->has('kode_108') && $request->kode_rs !== null) {
                $det = $data->details()->updateOrCreate(['kode_rs' => $request->kode_rs], $second);
            }

            DB::commit();
            $user = auth()->user();
            $pegawai = Pegawai::find($user->pegawai_id);
            if ($data->wasRecentlyCreated) {
                $data->update([
                    'created_by' => $pegawai->id
                ]);
                return new JsonResponse(['message' => 'data created', 'data' => $data], 201);
            }
            if ($data->wasChanged()) {
                $data->update([
                    'updated_by' => $pegawai->id
                ]);
                return new JsonResponse(['message' => 'data updated', 'data' => $data], 200);
            }
            if ($det->wasChanged()) {
                $data->update([
                    'updated_by' => $pegawai->id
                ]);
                return new JsonResponse(['message' => 'data updated', 'data' => $data], 200);
            }
            return new JsonResponse(['message' => 'No changes On header', 'data' => $data], 202);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 410);
        }
    }

    public static function updateStatus($reff, $status)
    {
        $data = Pemesanan::where('reff', $reff)->first();
        // return new JsonResponse(['message' => $data]);
        $data->status = $status;
        $data->update();
        if (!$data) {
            return new JsonResponse(['message' => 'update Gagal'], 500);
        }
        return new JsonResponse(['message' => 'update Berhasil'], 200);
    }

    public function destroy()
    {
        return new JsonResponse(['msg' => 'Belum ada bos']);
    }
    public function deleteDetails(Request $request)
    {
        $data = DetailPemesanan::find($request->id);
        $del = $data->delete();
        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        return new JsonResponse([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
