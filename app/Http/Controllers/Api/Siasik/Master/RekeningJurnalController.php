<?php

namespace App\Http\Controllers\Api\Siasik\Master;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Siasik\Master\Akun_mapjurnal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RekeningJurnalController extends Controller
{
    public function getRekening(){
        $rekening = Akun_mapjurnal::pluck('kode50')->toArray();
        $perPage = request('per_page', 100); // Default ke 100 per halaman, 0 untuk semua data

        $query = Akun50_2024::select('uraian', 'kodeall3', 'kodeall2')
            ->where('subrincian_objek', '!=', '')
            ->whereNotIn('kodeall3', $rekening);
        // Pencarian
        if (request('q')) {
            $cari = request('q');
            $query->where(function ($q) use ($cari) {
                $q->where('uraian', 'like', '%' . $cari . '%')
                  ->orWhere('kodeall3', 'like', '%' . $cari . '%');
            });
        }

        if ($perPage <= 0) {
            $akun = $query->get();
            return new JsonResponse(['data' => $akun]);
        }

        $akun = $query->simplePaginate($perPage);

        return new JsonResponse($akun);
    }

    public function index(){
        $data = Akun_mapjurnal::all();
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        try {
            $data = Akun_mapjurnal::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'data' => $data
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }
}
