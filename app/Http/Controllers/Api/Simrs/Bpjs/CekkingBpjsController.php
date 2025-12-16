<?php

namespace App\Http\Controllers\Api\Simrs\Bpjs;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CekkingBpjsController extends Controller
{
    public function ceksep()
    {
        return BridgingbpjsHelper::get_url('vclaim', '/SEP/' . request('nosep'));
    }


    public function getDokterByPoli(Request $request )
    {

        $jenis = $request->input('jenis');
        $kodePoli = $request->input('kodePoli');
        $tglRencanaKontrol = $request->input('tglRencanaKontrol');

        // $tgl = $this->formatTanggal($tglRencanaKontrol);

        $result = BridgingbpjsHelper::get_url('vclaim', "/RencanaKontrol/JadwalPraktekDokter/JnsKontrol/{$jenis}/KdPoli/{$kodePoli}/TglRencanaKontrol/{$tglRencanaKontrol}");

        return response()->json($result);
    }

    private function formatTanggal($tanggal)
    {
        // Konversi format tanggal dari d/m/Y ke Y-m-d
        $date = \DateTime::createFromFormat('d/m/Y', $tanggal);
        return $date->format('Y-m-d');
    }
}
