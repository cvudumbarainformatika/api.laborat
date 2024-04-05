<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Neonatus;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Pelayanan\NeonatusMedis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NeonatusMedisController extends Controller
{
    public function store(Request $request)
    {
      $user = auth()->user()->pegawai_id;
      $request->request->add(['user_input' => $user]);
      $formSave = $request->except(['riwayatKehamilan']);
      $saved = NeonatusMedis::create($formSave);

      if (!$saved) {
        return new JsonResponse(['message'=> 'failed'], 500);
      }

      return new JsonResponse($saved, 200);  
    }
}
