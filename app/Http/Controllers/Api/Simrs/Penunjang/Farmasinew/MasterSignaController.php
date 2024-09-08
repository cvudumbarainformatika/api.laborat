<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Msigna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterSignaController extends Controller
{
    public function list()
    {

        $list = Msigna::where('signa', 'Like', '%' . request('q') . '%')
            ->orderBy('id', 'DESC')
            ->paginate(request('per_page'));
        $data = collect($list)['data'];
        $meta = collect($list)->except('data');
        return new JsonResponse([
            'data' => $data,
            'meta' => $meta,
        ]);
    }
    public function simpan(Request $request)
    {
        $data = Msigna::updateOrCreate(
            ['id' => $request->id],
            $request->all()
        );
        return new JsonResponse([
            'message' => 'Sudah Disiampan',
            'data' => $data,
        ]);
    }
    public function hapus(Request $request)
    {
        $data = Msigna::find($request->id);
        if (!$data) {
            return new JsonResponse([
                'message' => 'Gagal Hapus, data tidak ditemukan',
                'data' => $data,
            ], 410);
        }
        $data->delete();
        return new JsonResponse([
            'message' => 'Sudah Dihapus',
            'data' => $data,
        ]);
    }
}
