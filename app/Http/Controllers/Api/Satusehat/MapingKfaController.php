<?php

namespace App\Http\Controllers\Api\Satusehat;

use App\Helpers\AuthSatsetHelper;
use App\Helpers\BridgingSatsetHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapingKfaController extends Controller
{
    public function getMasterObat()
    {
        $obat = Mobatnew::select('kd_obat', 'nama_obat', 'kode_kfa')->where('nama_obat', 'LIKE', '%' . request('q') . '%')->paginate(request('per_page'));
        $data = collect($obat)['data'];
        $meta = collect($obat)->except('data');

        return new JsonResponse([
            'data' => $data,
            'meta' => $meta,
            'obat' => $obat,
            'req' => request()->all(),
        ]);
    }
    public function getKfa()
    {
        $extend = '/kfa-v2/products/all';
        $token = AuthSatsetHelper::accessToken();
        $param = '?page=' . request('page') . '&size=' . request('per_page') . '&product_type=farmasi' . '&keyword=' . request('q');
        // $param = '?page=' . request('page') . '&size=' . request('per_page') . '&product_type=farmasi' . '&template_code=' . request('q');

        $obat = BridgingSatsetHelper::get_data_kfa($extend, $token, $param);
        $data = $obat['items']['data'] ?? [];
        // $adaur = (int)$obat['page'] ?? 0 < (int)$obat['total'] ?? 0 ? 'ada' : null;
        $meta = [
            'current_page' => $obat['page'] ?? 1,
            'last_page' => $obat['total'] ?? 1,
            'total' => $obat['total'] ?? 1,
            'total' => $obat['total'] ?? 1,
            // 'next_page_url' => $adaur,
        ];

        return new JsonResponse([
            'data' => $data,
            'meta' => $meta,
            'obat' => $obat,
            'token' => $token,
            'req' => request()->all(),
        ]);
    }
    public function simpanMapingKfa(Request $request)
    {

        $data = Mobatnew::where('kd_obat', $request->kd_obat)->first();
        if (!$data) {
            return new JsonResponse([
                'message' => 'Data Obat Tidak Ditemukan'
            ], 410);
        }
        $data->update([
            'kode_kfa' => $request->kode_kfa
        ]);
        return new JsonResponse([
            'message' => 'data berhasil disimpan',
            'data' => $data
        ]);
    }
}
