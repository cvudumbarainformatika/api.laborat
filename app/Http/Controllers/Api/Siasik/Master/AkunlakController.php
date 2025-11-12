<?php

namespace App\Http\Controllers\Api\Siasik\Master;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Siasik\Master\Akun_lak;
use App\Models\Siasik\Master\Akun_mapjurnal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class AkunlakController extends Controller
{
     public function index(){
        $data = Akun_lak::when(request('q'),function ($query) {
            $query->where('uraian', 'LIKE', '%' . request('q') . '%')
            ->orWhere('jenis', 'LIKE', '%' . request('q') . '%')
            ;
        })->get();
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $kodejenis = $request->kodejenis;

            if (empty($request->kode)) {
                DB::connection('siasik')->select('call akunlak(@nomor)');
                $x = DB::connection('siasik')->table('conter')->select('akunlak')->first();

                if (!$x) {
                    throw new \Exception('Gagal mendapatkan nomor dari prosedur notadinas');
                }

                $nomer = (int)$x->akunlak;
                $kode = FormatingHelper::kodeakun_lak($nomer, $kodejenis);
            } else {
                $kode = $request->kode;
            }

            $save = Akun_lak::updateOrCreate(
                [
                    'kode' => $kode
                ],
                [
                    'uraian' => $request->uraian ?? '',
                    'jenis' => $request->jenis ?? '',
                ]
            );

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'data' => $save
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            // Validasi dulu biar gak kosong
            $request->validate([
                'id' => 'required'
            ]);

            DB::beginTransaction();

            $data = Akun_lak::find($request->id);

            $data->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil dihapus',
                'data' => $data
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}
