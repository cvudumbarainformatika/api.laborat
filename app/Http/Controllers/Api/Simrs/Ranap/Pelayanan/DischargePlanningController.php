<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Anamnesis\KeluhanNyeri;
use App\Models\Simrs\Edukasi\DischargePlanning;
use App\Models\Simrs\Ranap\Pelayanan\Cppt;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanSambung;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanUmum;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\Penilaian;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DischargePlanningController extends Controller
{
    
    public function simpandata(Request $request)
    {

      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;
      //  return $anamnesis;
       



       $data = DischargePlanning::create([
        'rs1' => $request->noreg,
        'rs2' => $request->norm,
        'rs3' => date('Y-m-d H:i:s'),
        'rs4' => $request->lanjutan,
        'rs5' => $request->dokter,
        'rs6' => $request->ruang,
        'rs7'=> $request->kdsistembayar,
        'lamaPerawatan'=> $request->lamaPerawatan,
        'tglRencanaPlg'=> $request->tglRencanaPlg,
        'pldiRumah' => $request->pldiRumah,
        'transportasi' => $request->transportasi,
        'prognosis' => $request->prognosis,
        'user' => $kdpegsimrs

       ]);

       if (!$data) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Gagal menyimpan data'
        ]);
       }

       return new JsonResponse([
        'success' => true,
        'message' => 'success',
        'result' => $data
       ]);
    }


   


    
}
