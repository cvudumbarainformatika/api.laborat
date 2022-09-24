<?php

namespace App\Http\Controllers\Api\penunjang;

use App\Http\Controllers\Controller;
use App\Models\LaboratLuar;
use App\Models\Pasien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiLaboratLuarController extends Controller
{
    public function index()
    {
        $now = date('Y-m-d');
        $to = date('2018-05-02');
        $query = LaboratLuar::query()
                ->selectRaw('nota,tgl,nama,kelamin,alamat,tgl_lahir,pengirim,perusahaan_id,lunas,akhir,akhirx')
                ->with(['perusahaan'])
                ->groupBy('nota')
                ->latest('id');
                // ->whereDate('rs3', '=', $now);
        $data = $query->paginate(request('per_page'));
        // $count = collect($query->get())->count();
                // ->simplePaginate(request('per_page'));

       return new JsonResponse($data);
    }

    public function get_details()
    {
        $data = LaboratLuar::where('nota', request('nota'))
                ->with(['perusahaan', 'pemeriksaan_laborat'])->get();

        return new JsonResponse($data);
    }
}
