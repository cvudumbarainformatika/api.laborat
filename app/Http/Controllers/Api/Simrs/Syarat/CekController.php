<?php

namespace App\Http\Controllers\Api\Simrs\Syarat;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Planing\Planing_Igd_Lama;
use App\Models\Simrs\Planing\Plann_Igd_Ranap_Ruang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CekController extends Controller
{
    public static function ceknoktp($norm)
    {
        $cekidentitas = Mpasien::where('rs1', $norm)->first();
        if($cekidentitas->rs49 === '' || $cekidentitas->rs49 === null){
            //return new JsonResponse(['message' => 'Maaf Identitas Pasien Belum Lengkap, Hubungi Pendaftaran Pasien Untuk Melengkapi Identias Pasien...!!!'], 500);
            return "1";
        }else{
            return "2";
        }
    }

    public static function cekplan($noreg)
    {
        $cek = Planing_Igd_Lama::where('rs1', $noreg)->count();
        if($cek === 0){
            return "1";
        }else{
            return "2";
        }
    }

   public static function cekindikasimasukranap($noreg)
    {
        $cari = Planing_Igd_Lama::query()
            ->join('rs24', 'rs24.rs1', '=', 'rs141.rs5')
            ->where('rs141.rs1', $noreg)
            ->where('rs141.rs3', 'POL014')
            ->where('rs141.rs4', 'Rawat Inap')
            ->select(
                'rs141.rs1 as noreg',
                'rs141.rs2 as norm',
                'rs141.rs5 as ruangan',
                'rs24.rs3 as kelas'
            )
            ->first();

        // Data planning rawat inap tidak ditemukan
        if (!$cari) {
            return 0;
        }

        $kelasKhusus = ['NICU', 'ICC', 'IC', 'HCU'];

        if (in_array($cari->kelas, $kelasKhusus, true)) {
            return Plann_Igd_Ranap_Ruang::where('noreg', $noreg)->exists()
                ? 1
                : 0;
        }

        return 1;
    }
}
