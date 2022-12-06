<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\BarangRSResource;
use App\Models\Sigarang\BarangRS;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BarangRSController extends Controller
{
    public function index()
    {
        // $data = BarangRS::paginate();
        $data = BarangRS::latest()
            ->filter(request(['q']))
            ->with('satuan')
            ->paginate(request('per_page'));
        // return BarangRSResource::collection($data);
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    public function barangrs()
    {
        $data = BarangRS::latest('id')->filter(request(['q']))->get(); //paginate(request('per_page'));
        return BarangRSResource::collection($data);
    }
    public function store(Request $request)
    {
        // $auth = $request->user();
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'kode' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }


                BarangRS::firstOrCreate($request->all());

                // $auth->log("Memasukkan data BarangRS {$user->name}");
            } else {
                $toUpdate = $request->all();
                unset($toUpdate['id']);
                $barang = BarangRS::find($request->id);
                $barang->update($toUpdate);

                // $auth->log("Merubah data BarangRS {$user->name}");
            }

            DB::commit();
            return response()->json(['message' => 'success'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e, 'request' => $request->all()], 500);
        }
    }
    public function destroy(Request $request)
    {

        // $auth = auth()->user()->id;
        $id = $request->id;

        $data = BarangRS::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data BarangRS {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
