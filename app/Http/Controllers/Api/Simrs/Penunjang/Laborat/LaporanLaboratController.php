<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Laborat;

use App\Events\NotifMessageEvent;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Penunjang\Laborat\LaboratMeta;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Laborat\MasterLaborat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LaporanLaboratController extends Controller
{
  public function masterlaborat()
  {
     $data = MasterLaborat::select(
       'rs1 as kode',
       'rs2 as pemeriksaan',
      //  'rs3 as hargasaranapoliumum',
      //  'rs4 as hargapelayananpoliumum',
      //  'rs5 as hargasaranapolispesialis',
      //  'rs6 as hargapelayananpolispesialis',
       'rs21 as gruper',
       'nilainormal',
       'satuan',
       'jenislab'
     )
    //  ->where('rs25', '1')
     ->where('hidden', '!=', '1')
     ->orderBy('tampilanurut', 'asc')
     ->orderBy('rs2')
     ->get();
     return new JsonResponse($data);
  }
}
