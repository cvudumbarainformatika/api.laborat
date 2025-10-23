<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Homecare;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\DaftarrajalController;
use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Homecare\HomeCareAdmin;
use App\Models\Simrs\Homecare\HomeCareKunjungan;
use App\Models\Simrs\Master\Mpasien;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendaftaranHomeCareController extends Controller
{
    public function getDokter()
    {
        $data = Petugas::select(
            'nama',
            'kdpegsimrs as dpjp'
        )->where('kdgroupnakes', '1')->where('aktif', 'aktif')->get();
        return new JsonResponse($data);
    }
    public function layananAdminHomeCare()
    {
        $data = HomeCareAdmin::whereNull('flag')->get();
        return new JsonResponse($data);
    }
    public function listKunjungan()
    {

        $data = request()->all();
        $meta = request()->all();
        return new JsonResponse([
            'meta' => $meta,
            'data' => $data
        ]);
    }
    public function simpanKunjungan(Request $request)
    {
        // cek norm dan nik jika pasien baru
        if ($request->barulama === 'baru') {
            $data = Mpasien::where('rs1', $request->norm)->first();
            if ($data) {
                return new JsonResponse([
                    'message' => 'Nomor RM Sudah ada',
                    'data' => $data
                ], 410);
            }
            $data2 = Mpasien::where('rs49', $request->nik)->first();
            if ($data2) {
                return new JsonResponse([
                    'message' => 'NIK Sudah ada',
                    'data' => $data
                ], 410);
            }
        }
        $masterpasien = DaftarrajalController::simpanMpasien($request);
        if (!$masterpasien) {
            return new JsonResponse(['message' => 'DATA MASTER PASIEN GAGAL DISIMPAN/DIUPDATE'], 410);
        }
        $nomor = str_pad(date('dHis'), 10, '0', STR_PAD_LEFT);
        $noreg = $nomor . "/" . date("m") . "/" . date("Y") . "/H";
        // cek unique noreg
        $ada = HomeCareKunjungan::where('noreg', $noreg)->first();
        if ($ada) return new JsonResponse(['message' => 'Noreg Sudah Ada, silahkan coba simpan lagi.'], 410);
        $user = auth()->user();
        $simpan = HomeCareKunjungan::create([
            'noreg' => $noreg,
            'norm' => $request->norm,
            'kode_poli' => $request->kode_poli,
            'tgl_kunjungan' => $request->tglmasuk,
            'kode_admin_layanan' => $request->kode_layanan,
            'nama_admin_layanan' => $request->nama_layanan,
            'sistem_bayar' => $request->sistembayar,
            'administrasi' => $request->administrasi,
            'js' => $request->js,
            'jp' => $request->jp,
            'dpjp' => $request->dpjp,
            'id_pegawai' => $user->pegawai_id,
        ]);

        return new JsonResponse([
            'data' => $simpan,
            'message' => 'Pendaftaran Kunjungan Home Care sudah disimpan'
        ]);
    }
}
