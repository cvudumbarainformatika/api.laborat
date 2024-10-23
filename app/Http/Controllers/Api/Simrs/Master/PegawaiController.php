<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Master\Mobat;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PegawaiController extends Controller
{
    public function listnakes()
    {
       $data = Cache::rememberForever('list_nakes', function () {
        $kd=['1','2','3'];
        return Petugas::select('nama','nik','nip','kdpegsimrs', 'kdgroupnakes','kddpjp','foto')
        ->whereIn('kdgroupnakes', $kd)->where('aktif', 'AKTIF')
        ->get();
      });
      return new JsonResponse($data);
    }
}
