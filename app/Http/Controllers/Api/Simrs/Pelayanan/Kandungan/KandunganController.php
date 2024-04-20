<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Kandungan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Pelayanan\Kandungan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KandunganController extends Controller
{

    public function store(Request $request)
    {
      $user = auth()->user()->pegawai_id;
      $request->request->add(['user_input' => $user]);
      $saved = Kandungan::create($request->all());

      if (!$saved) {
        return new JsonResponse(['message'=> 'failed'], 500);
      }

      return new JsonResponse($saved, 200);   
    }
    public function deletedata(Request $request)
    {
      
      $data = Kandungan::find($request->id);

      if (!$data) {
        return new JsonResponse(['message'=> 'Data tidak ditemukan'], 500);
      }

      $del = $data->delete();

      if (!$del) {
        return new JsonResponse(['message'=> 'Failed'], 500);
      }

      return new JsonResponse(['message'=> 'Data Berhasil dihapus'], 200); 
    }
}
