<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Pediatri;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\MwhocdcAnak;
use App\Models\Simrs\Pelayanan\Pediatri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PediatriController extends Controller
{
    public function store(Request $request)
    {
      $user = auth()->user()->pegawai_id;
      $request->request->add(['user_input' => $user]);
      $formSave = $request->all();
      $saved = Pediatri::create($formSave);

      if (!$saved) {
        return new JsonResponse(['message'=> 'failed'], 500);
      }

      return new JsonResponse($saved->load('pegawai'), 200);  
    }

    public function deletedata(Request $request)
    {
      
      $data = Pediatri::find($request->id);

      if (!$data) {
        return new JsonResponse(['message'=> 'Data tidak ditemukan'], 500);
      }

      $del = $data->delete();

      if (!$del) {
        return new JsonResponse(['message'=> 'Failed'], 500);
      }

      return new JsonResponse(['message'=> 'Data Berhasil dihapus'], 200); 
    }

    public function master_who_cdc()
    {
       $thumb = collect();
        MwhocdcAnak::where('dr_tanggal', '=', null)
          ->orderBy('id')
          ->chunk(50, function ($dokters) use ($thumb) {
              foreach ($dokters as $q) {
                  $thumb->push($q);
              }
          });


        return new JsonResponse($thumb);
    }

    
}
