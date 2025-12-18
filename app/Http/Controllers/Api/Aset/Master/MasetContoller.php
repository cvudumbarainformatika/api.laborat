<?php

namespace App\Http\Controllers\Api\Aset\Master;

use App\Http\Controllers\Controller;
use App\Models\Aset\Master\Maset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasetContoller extends Controller
{
    public function index()
    {
        $data = Maset::whereIsnull('flaging')
        ->when(request('q'), function($query){
            $query->where('nama', 'like', '%'.request('q').'%')
            ->orWhere('kode', 'like', '%'.request('q').'%')
            ->orWhere('kd108', 'like', '%'.request('q').'%')
            ->orWhere('uraian108', 'like', '%'.request('q').'%')
            ->orWhere('kd50', 'like', '%'.request('q').'%')
            ->orWhere('uraian50', 'like', '%'.request('q').'%')
            ->orWhere('kdaspak', 'like', '%'.request('q').'%')
            ->orWhere('uraianaspak', 'like', '%'.request('q').'%');
        })
        ->paginate(request('per_page'));
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $data = Maset::updateOrCreate($request->all());
        return new JsonResponse(['message' => 'Data berhasil disimpan','data' => $data]);
    }

    public function delete(Request $request)
    {
        $data = Maset::find($request->id);
        $data->flaging = 1;
        $data->save();
        return new JsonResponse(['message' => 'Data berhasil dihapus']);
    }
}
