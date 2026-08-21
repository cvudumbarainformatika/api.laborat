<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Pelayanan\LaporanEswl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaporanEswlController extends Controller
{
    public function simpan(Request $request)
    {
        $id = $request->input('id');
        
        if ($id) {
            $data = LaporanEswl::find($id);
            if ($data) {
                $data->update($request->all());
                return new JsonResponse($data, 200);
            }
        }

        $saved = LaporanEswl::create($request->all());

        if (!$saved) {
            return new JsonResponse(['message' => 'Gagal menyimpan data laporan ESWL'], 500);
        }

        return new JsonResponse($saved, 200);
    }

    public function hapus(Request $request)
    {
        $data = LaporanEswl::find($request->id);
        
        if (!$data) {
            return new JsonResponse(['message' => 'Maaf, data tidak ditemukan'], 404);
        }
        
        $data->delete();
        
        return new JsonResponse(['message' => 'Data laporan ESWL berhasil dihapus'], 200);
    }
}
