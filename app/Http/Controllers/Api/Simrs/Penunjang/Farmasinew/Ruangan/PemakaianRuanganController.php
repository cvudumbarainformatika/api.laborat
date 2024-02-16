<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Ruangan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemakaianRuanganController extends Controller
{
    //
    public function getStokRuangan()
    {
        $obat = Stokreal::selectRaw('*, sum(jumlah) as stok')
            ->with('obat:kd_obat,nama_obat,satuan_k', 'ruang:kode,uraian')
            ->where('jumlah', '>', 0)
            ->where('kdruang', request('kdruang'))
            ->when(request('q'), function ($query) {
                $kode = Mobatnew::select('kd_obat')
                    ->where('kd_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->get();
                $query->whereIn('kdobat', $kode);
            })
            ->groupBy('kdobat', 'kdruang')
            ->paginate(request('per_page'));

        return new JsonResponse($obat);
    }

    public function simpanpemaikaianruangan(Request $request)
    {

        return new JsonResponse($request->all());

        DB::select('call pemakaianruangan(@nomor)');
        $x = DB::table('conter')->select('pemakaianruangan')->get();
        $wew = $x[0]->pemakaianruangan;
        $pemakaianruangan = FormatingHelper::nopemakaianruangan($wew, 'RUA-FAR');
    }

    public function selesaiPakai(Request $request)
    {

        return new JsonResponse($request->all());
    }
}
