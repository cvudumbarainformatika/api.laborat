<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Jabatan;
use App\Models\Simpeg\Petugas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpjOpnameController extends Controller
{
    //
    public function getOpname()
    {
        $data = request()->all();
        return new JsonResponse($data);
    }
    public function getKepala()
    {
        $data['farmasi'] = Petugas::select('id', 'nama', 'nip', 'nip_baru', 'nik', 'jabatan', 'jabatan_tmb')
            ->where('aktif', 'AKTIF')
            ->where(function ($q) {
                $q->where('jabatan', 'J00270')
                    ->orWhere('jabatan_tmb', 'JT00014');
            })
            ->with('relasi_jabatan', 'jabatanTambahan')
            ->first();
        $data['keuangan'] = Petugas::select('id', 'nama', 'nip', 'nip_baru', 'nik', 'jabatan', 'jabatan_tmb')
            ->where('aktif', 'AKTIF')
            ->where('jabatan', 'J00005')
            ->with('relasi_jabatan', 'jabatanTambahan')
            ->first();
        $apoteker = Jabatan::select('kode_jabatan')->where('jabatan', 'like', '%Apoteker%')->pluck('kode_jabatan');
        $data['pegawai'] = Petugas::select('nama', 'id', 'kdpegsimrs', 'nip', 'nip_baru', 'nik', 'jabatan', 'jabatan_tmb')
            ->where('aktif', '=', 'AKTIF')
            ->where('ruang', '=', 'R00025')
            ->whereIn('jabatan', $apoteker)
            ->with('relasi_jabatan', 'jabatanTambahan')
            ->whereNotNull('satset_uuid')
            ->get();
        $data['pelaksanas'] = Petugas::select('nama', 'id', 'kdpegsimrs', 'nip', 'nip_baru', 'nik', 'jabatan', 'jabatan_tmb')
            ->where('aktif', '=', 'AKTIF')
            ->where('ruang', '=', 'R00025')
            ->where(function ($q) {
                $q->where('kdruangansim', 'like', '%Gd-05010100%')
                    ->orWhere('kdruangansim', 'like', '%Gd-04010103%')
                    ->orWhere('kdruangansim', 'like', '%Gd-04010102%')
                    ->orWhere('kdruangansim', 'like', '%Gd-03010101%')
                    ->orWhere('kdruangansim', 'like', '%Gd-03010100%')
                    ->orWhere('kdruangansim', 'like', '%Gd-05010101%')
                    ->orWhere('kdruangansim', 'like', '%Gd-02010104%');
            })
            ->with('relasi_jabatan', 'jabatanTambahan')
            ->whereNotNull('satset_uuid')
            ->get();
        return new JsonResponse($data);
    }
}
