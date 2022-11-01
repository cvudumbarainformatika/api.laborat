<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PermintaanruanganController extends Controller
{
    //
    public function draft()
    {
        $complete = Permintaanruangan::where('reff', '=', request()->reff)
            ->where('status', '=', 2)->get();
        $draft = Permintaanruangan::where('reff', '=', request()->reff)
            ->where('status', '=', 1)
            ->latest('id')->with(['details.barangrs', 'details.satuan', 'details.ruang', 'details.gudang'])->get();
        if (count($draft)) {
            $kolek = collect($draft[0]->details)->groupBy('dari');
            $draft[0]->gudang = $kolek;
        }
        if (count($complete)) {
            return new JsonResponse(['message' => 'completed']);
        }
        return new JsonResponse($draft);
    }

    // ambil semua permintaan yang sudah selesai di input
    public function getPerrmintaan()
    {
        $data = Permintaanruangan::where('status', '=', 2)->get();
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $second = $request->all();
        $second['tanggal'] = date('Y-m-d H:i:s');

        try {
            DB::beginTransaction();

            $valid = Validator::make($request->all(), ['reff' => 'required']);
            if ($valid->fails()) {
                return new JsonResponse($valid->errors(), 422);
            }

            $data = Permintaanruangan::updateOrCreate(['reff' => $request->reff], $second);

            if ($request->has('kode_rs') && $request->kode_rs !== null) {
                $data->details()->updateOrCreate(['kode_rs' => $request->kode_rs], $second);
            }

            DB::commit();

            return new JsonResponse([
                'message' => 'success',
                'data' => $data,
                // 'gudang' => $gudang,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }
    public function selesaiInput(Request $req)
    {
        $data = Permintaanruangan::where('reff', $req->reff)->first();
        $data->status = 2;
        if (!$data->save()) {
            return new JsonResponse(['message' => 'Gagal Update Status']);
        }
        return new JsonResponse(['message' => 'Input telah dinyatakan Selesai']);
    }
}
