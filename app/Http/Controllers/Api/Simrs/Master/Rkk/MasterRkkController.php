<?php

namespace App\Http\Controllers\Api\Simrs\Master\Rkk;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mdiagnosakeperawatan;
use App\Models\Simrs\Master\Mintervensikeperawatan;
use App\Models\Simrs\Master\Mpemeriksaanfisik;
use App\Models\Simrs\Master\Mrkk;
use App\Models\Simrs\Master\Mtemplategambar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MasterRkkController extends Controller
{

    public function index()
    {
        $data = cache()->remember('m_rkk', now()->addHours(8), function ()  {
            return Mrkk::all();
        });

        return new JsonResponse([
            'message' => 'success',
            'result' => $data
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'kode' => [
                'required', Rule::unique('m_rkk', 'no')->ignore($request->id, 'id')
            ],
            'jenjang' => 'required',

        ]);

        if ($validator->fails()) {
            return new JsonResponse(['status' => false, 'message' => $validator->errors()], 201);
        }
        $data = Mrkk::updateOrCreate(
            ['kode' => $request->kode],
            [
                'nama' => $request->nama,
                'jenjang' => $request->jenjang,
                'jenis' => $request->jenis,
            
            ]
        );

        if (!$data) {
            return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
        }

        // refresh cache
        Cache::forget('m_rkk');

        return new JsonResponse([
            'message' => 'Data Berhasil Disimpan...!!!',
            'result' => $data
        ], 200);
    }

    public function delete(Request $request)
    {
        $data = Mrkk::find($request->id);

        if (!$data) {
            return new JsonResponse(['message' => 'Maaf, Data Tidak ditemukan...!!!'], 500);
        }

        $data->delete();
        // refresh cache
        Cache::forget('m_rkk');
        return new JsonResponse([
            'message' => 'Data Berhasil dihapus...!!!',
        ], 200);
    }

   
}
