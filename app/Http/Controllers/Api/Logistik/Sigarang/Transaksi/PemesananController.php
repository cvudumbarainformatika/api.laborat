<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\Transaksi\PemesananResource;
use App\Models\Sigarang\Transaksi\Pemesanan\Pemesanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PemesananController extends Controller
{
    //

    public function draft()
    {
        $complete = Pemesanan::where('reff', '=', request()->reff)
            ->where('status', '=', 2)->get();
        $draft = Pemesanan::where('reff', '=', request()->reff)
            ->where('status', '=', 1)
            ->latest('id')->with(['details.barang108', 'details.barangrs', 'details.satuan'])->get();
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
        unset($second['reff']);
        try {
            DB::beginTransaction();

            $valid = Validator::make($request->all(), ['reff' => 'required']);
            if ($valid->fails()) {
                return new JsonResponse($valid->errors(), 422);
            }

            $data = Pemesanan::updateOrCreate(['reff' => $request->reff], $second);
            if ($request->has('kode_rs') && $request->has('kode_108') && $request->kode_rs !== null) {
                $data->details()->updateOrCreate(['kode_rs' => $request->kode_rs], $second);
            }

            DB::commit();
            return new JsonResponse(['message' => 'success'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }

    public static function updateStatus($nomor, $status)
    {
        $data = Pemesanan::where('nomor', $nomor)->first();
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
}
