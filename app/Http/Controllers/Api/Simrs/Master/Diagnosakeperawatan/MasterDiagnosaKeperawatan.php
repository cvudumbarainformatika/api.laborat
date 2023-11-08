<?php

namespace App\Http\Controllers\Api\Simrs\Master\Diagnosakeperawatan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mdiagnosakeperawatan;
use App\Models\Simrs\Master\Mpemeriksaanfisik;
use App\Models\Simrs\Master\Mtemplategambar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MasterDiagnosaKeperawatan extends Controller
{

    public function index()
    {
        $data = Mdiagnosakeperawatan::get();

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
                'required', Rule::unique('mdiagnosakeperawatan', 'kode')->ignore($request->id, 'id')
            ],
        ]);

        if ($validator->fails()) {
            return new JsonResponse(['status' => false, 'message' => $validator->errors()], 201);
        }
        $data = Mdiagnosakeperawatan::updateOrCreate(
            ['kode' => $request->kode],
            ['nama' => $request->nama]
        );

        if (!$data) {
            return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
        }

        return new JsonResponse([
            'message' => 'Data Berhasil Disimpan...!!!',
            'result' => $data
        ], 200);
    }

    public function delete(Request $request)
    {
        $data = Mdiagnosakeperawatan::find($request->id);

        if (!$data) {
            return new JsonResponse(['message' => 'Maaf, Data Tidak ditemukan...!!!'], 500);
        }

        $data->delete();

        return new JsonResponse([
            'message' => 'Data Berhasil dihapus...!!!',
        ], 200);
    }
}
