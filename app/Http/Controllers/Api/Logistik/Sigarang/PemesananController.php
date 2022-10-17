<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PemesananController extends Controller
{
    // public function index()
    // {
    //     // $data = Pemesanan::paginate();
    //     $data = Pemesanan::latest()
    //         ->filter(request(['q']))
    //         ->with('barangrs', 'barang108', 'satuan', 'perusahaan')
    //         ->paginate(request('per_page'));
    //     return PemesananResource::collection($data);
    // }
    // public function pemesanan()
    // {
    //     $data = Pemesanan::latest('id')->filter(request(['q']))->get(); //paginate(request('per_page'));
    //     return PemesananResource::collection($data);
    //     // return new JsonResponse($data);
    // }
    // public function store(Request $request)
    // {
    //     // $auth = $request->user();
    //     $balikin = null;
    //     try {

    //         DB::beginTransaction();


    //         $validatedData = Validator::make($request->all(), [
    //             'pemesanan' => 'required'
    //         ]);
    //         if ($validatedData->fails()) {
    //             return response()->json($validatedData->errors(), 422);
    //         }
    //         $balikin = $request->all();
    //         unset($balikin['pemesanan']);
    //         Pemesanan::updateOrCreate($request->only('pemesanan'), $balikin);
    //         // Pemesanan::firstOrCreate([
    //         //     'nama' => $request->nama,
    //         //     'nomor' => $request->nomor
    //         // ]);

    //         // $auth->log("Memasukkan data Pemesanan {$user->name}");
    //         //     if (!$request->has('id')) {
    //         // } else {
    //         //     $pesanan = Pemesanan::find($request->id);
    //         //     $pesanan->update($request->all());
    //         //     // $gedung->update([
    //         //     //     'nomor' => $request->nomor,
    //         //     //     'nama' => $request->nama
    //         //     // ]);

    //         //     // $auth->log("Merubah data Pemesanan {$user->name}");
    //         // }

    //         DB::commit();
    //         return response()->json(['message' => 'success'], 201);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json(['message' => 'ada kesalahan', 'error' => $e, 'balikin' => $balikin], 500);
    //     }
    // }
    // public function destroy(Request $request)
    // {

    //     // $auth = auth()->user()->id;
    //     $id = $request->id;

    //     $data = Pemesanan::find($id);
    //     $del = $data->delete();

    //     if (!$del) {
    //         return response()->json([
    //             'message' => 'Error on Delete'
    //         ], 500);
    //     }

    //     // $user->log("Menghapus Data Pemesanan {$data->nama}");
    //     return response()->json([
    //         'message' => 'Data sukses terhapus'
    //     ], 200);
    // }
}
