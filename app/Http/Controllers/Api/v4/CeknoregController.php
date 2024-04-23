<?php

namespace App\Http\Controllers\Api\v4;

use App\Events\NotifMessageEvent;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CeknoregController extends Controller
{
  public function cek(Request $request)
  {
    $str=$request->noreg;
    $decode=base64_decode($str);
    if (!$decode) {
      return new JsonResponse(['message' => 'invalid'], 500);
    }
    $split= explode('|', $decode);
    if (count($split)<1) {
      return new JsonResponse(['message' => 'invalid'], 500);
    }

    $noreg=$split[0];

    $cekx = KunjunganPoli::select('rs1', 'rs2','rs3', 'rs9', 'rs19')->where('rs1', $noreg)
    ->with(['datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp,ttdpegawai'])->first();
    if (!$cekx) {
      return new JsonResponse(['message' => 'invalid'], 500);
    }
    return new JsonResponse($cekx);
  }
}
