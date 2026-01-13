<?php

namespace App\Http\Controllers\Api\Aset\Master;

use App\Http\Controllers\Controller;
use App\Models\Aset\Master\Maset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasetContoller extends Controller
{
    public function index()
    {
        $data = Maset::whereNull('flaging')
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
        $validated = $request->validate(
            [
                'kdaset' => 'nullable',
                'namaaset' => 'required',
                'kd50' => 'required',
                'kd108' => 'required',
                'kdaspak' => 'required',
                'uraian50' => 'required',
                'uraian108' => 'required',
                'uraianaspak' => 'required',
                'satuan' => 'required',
            ],
            [
                'namaaset.required' => 'Nama Aset harus diisi',
                'kd50.required' => 'Kode 50 harus diisi',
                'kd108.required' => 'Kode 108 harus diisi',
                'kdaspak.required' => 'Kode Aspak harus diisi',
                'uraian50.required' => 'Uraian 50 harus diisi',
                'uraian108.required' => 'Uraian 108 harus diisi',
                'uraianaspak.required' => 'Uraian Aspak harus diisi',
                'satuan.required' => 'Satuan harus diisi',
                ]
        );
        try{
            DB::beginTransaction();
                if($validated['kdaset'] == null){
                    $total = Maset::count();
                    $kdaset = 'ASET'.str_pad($total+1, 3, '0', STR_PAD_LEFT);
                }else{
                    $kdaset = $validated['kdaset'];
                }
                $data = Maset::updateOrCreate(
                    ['kdaset' => $kdaset],
                    [
                    'namaaset' => $validated['namaaset'],
                    'kd50' => $validated['kd50'],
                    'kd108' => $validated['kd108'],
                    'kdaspak' => $validated['kdaspak'],
                    'uraian50' => $validated['uraian50'],
                    'uraian108' => $validated['uraian108'],
                    'uraianaspak' => $validated['uraianaspak'],
                    'satuan' => $validated['satuan'],
                ]);
                return new JsonResponse(['message' => 'Data berhasil disimpan','data' => $data]);
            DB::commit();
        }catch(\Exception $e){
            DB::rollback();
            return new JsonResponse(['message' => 'Data gagal disimpan','error' => $e->getMessage()]);
        }
    }

    public function delete(Request $request)
    {
        $data = Maset::find($request->id);
        $data->flaging = 1;
        $data->save();
        return new JsonResponse(['message' => 'Data berhasil dihapus']);
    }
}
