<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\DischargePlanning\DischargePlanning;
use App\Models\Simrs\DischargePlanning\SkriningPulang;
use App\Models\Simrs\DischargePlanning\SummaryPulang;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DischargePlanningController extends Controller
{
    
    public function getmasterprognosis()
    {
        $prognosis = DB::table('rs27')->select('*')->where('flag','')->get();
        return new JsonResponse($prognosis);
    }
    public function simpandata(Request $request)
    {

      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;
      //  return $anamnesis;
       



       $data = DischargePlanning::create([
        'rs1' => $request->noreg,
        'rs2' => $request->norm,
        'rs3' => date('Y-m-d H:i:s'),
        'rs4' => $request->anjuran,
        'rs5' => $request->dokter,
        'rs6' => $request->ruangan,
        'rs7'=> $request->kodesistembayar,
        'kdruang'=> $request->kdruang,
        'lamaPerawatan'=> $request->lamaPerawatan,
        'tglRencanaPlg'=> $request->tglRencanaPlg,
        'bayiTglBersama'=> $request->bayiTglBersama,
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
    public function simpandataskrining(Request $request)
    {

      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;

       $data = SkriningPulang::create([
        'rs1' => $request->rs1,
        'rs2' => $request->rs2,
        'rs3' => date('Y-m-d H:i:s'),
        'rs4' => $request->rs4,
        'rs5' => $request->rs5,
        'rs5Ket' => $request->rs5Ket,
        'rs6' => $request->rs6,
        'rs7'=> $request->rs7,
        'rs8'=> $request->rs8,
        'rs9'=> $request->rs9,
        'rs10'=> $request->rs10,
        'rs11'=> $request->rs11,
        'rs12'=> $request->rs12,
        'rs13'=> $request->rs13,
        'rs14'=> $request->rs14,
        'rs15'=> $request->rs15,
        'rs16'=> $request->rs16,
        'rs16Ket'=> $request->rs16Ket,
        'rs17'=> $request->rs17,
        'rs18'=> $request->rs18,
        'rs18Ket'=> $request->rs18Ket,
        'rs19'=> $request->rs19,
        'rs20'=> $request->rs20,
        'rs21'=> $request->rs21,
        'rs22'=> $request->rs22,
        'kdruang'=> $request->kdruang,
        'tglRencanaPulang'=> $request->tglRencanaPlg,
        'user_input' => $kdpegsimrs

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

    public function simpandatasummary(Request $request)
    {

      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;

       $data = SummaryPulang::create([
        'rs1' => $request->rs1,
        'rs2' => $request->rs2,
        'rs3' => date('Y-m-d H:i:s'),
        'rs4' => $request->rs4,
        'rs5' => $request->rs5,
        'rs6' => $request->rs6,
        'rs7'=> $request->rs7,
        'rs8'=> $request->rs8,
        'rs9'=> $request->rs9,
        'rs10'=> $request->rs10,
        'kdruang'=> $request->kdruang,
        'ttdPasien'=> null,
        'user_input' => $kdpegsimrs 

       ]);

       if (!$data) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Gagal menyimpan data'
        ]);
       }

       $ttdPasien = $this->saveImage($request, $request->ttdPasien, $data->id);
       $cek = SummaryPulang::find($data->id);

       $cek->update([
        'ttdPasien' => $ttdPasien
       ]);

       return new JsonResponse([
        'success' => true,
        'message' => 'success',
        'result' => $cek
       ]);
    }

    static function saveImage($request, $image, $id)
    {

      $file=null;

      if ($image && $id) {
          $name = $id;
          $noreg = str_replace('/', '-', $request->noreg);
          $folderPath = "summary/" . $noreg .'_'.$request->jenis. '/';

          $image_parts = explode(";base64,", $image);
          $image_type_aux = explode("image/", $image_parts[0]);
          $image_type = $image_type_aux[1];
          $image_base64 = base64_decode($image_parts[1], true);
          $file = $folderPath . $name . '.' . $image_type;

          $imageName = $name . '.' . $image_type;
          Storage::delete('public/' . $folderPath . $imageName);
          Storage::disk('public')->put($folderPath . $imageName, $image_base64);
      }

      return $file;
    }


   public function hapusdata(Request $request)
   {
       $cari = DischargePlanning::find($request->id);
       if (!$cari) {
         return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
       }
       $cari->delete();
       return new JsonResponse(['message' => 'berhasil dihapus'], 200);
   }
   public function hapusdataskrining(Request $request)
   {
       $cari = SkriningPulang::find($request->id);
       if (!$cari) {
         return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
       }
       $cari->delete();
       return new JsonResponse(['message' => 'berhasil dihapus'], 200);
   }
   public function hapusdatasummary(Request $request)
   {
       $cari = SummaryPulang::find($request->id);
       if (!$cari) {
         return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
       }
       $cari->delete();
       return new JsonResponse(['message' => 'berhasil dihapus'], 200);
   }


    
}
