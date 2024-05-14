<?php

namespace App\Http\Controllers;

use App\Models\LaboratLuar;
use App\Models\TransaksiLaborat;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class PengesahanQrController extends Controller
{

    public function index()
    {
      $noreg = request('noreg');
      $dokumen = request('dokumen');
      
      $enc = base64_encode($noreg.'|'.$dokumen);

      // return response()->json([
      //   'noreg'=> $noreg,
      //   'dokumen'=> $dokumen
      // ]);

      return redirect()->away('https://rsud.probolinggokota.go.id/dokumen-simrs/legalitas/'.$enc);

    }
}
