<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Planing\Planningdokter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TargetController extends Controller
{
    public function simpan(Request $request)
    {
        $pegawai = Petugas::find(auth()->user()->pegawai_id);
        $userKdPeg = $pegawai->kdpegsimrs ?? auth()->user()->username;

        $data = null;
        if ($request->id === null) {
            $data = new Planningdokter();
        } else {
            $data = Planningdokter::find($request->id);
            if (!$data) {
                $data = new Planningdokter();
            }
        }

        $data->noreg = $request->noreg;
        $data->norm = $request->norm;
        $data->target = $request->target;
        $data->terapi = $request->terapi;
        $data->monitor = $request->monitor;
        $data->kie = $request->kie;
        $data->kdruang = $request->kdruang;
        $data->user = $userKdPeg;

        // Cek apakah user yang input adalah DPJP pasien
        $kunjungan = DB::table('rs23')->select('rs10')->where('rs1', $request->noreg)->first();
        $isDpjp = ($kunjungan && $kunjungan->rs10 == $userKdPeg);

        if ($isDpjp) {
            // Jika yang mengisi adalah DPJP sendiri, otomatis terverifikasi
            $data->verif_dpjp = 1;
            $data->tgl_verif_dpjp = date('Y-m-d H:i:s');
            $data->user_verif_dpjp = $userKdPeg;
        } else {
            // Jika yang mengisi BUKAN DPJP (misal dokter jaga/asisten), butuh verifikasi DPJP
            $data->verif_dpjp = 0;
            $data->tgl_verif_dpjp = null;
            $data->user_verif_dpjp = null;
        }

        $data->save();

        return new JsonResponse($data);
    }

    public function verif(Request $request)
    {
        $pegawai = Petugas::find(auth()->user()->pegawai_id);
        $userKdPeg = $pegawai->kdpegsimrs ?? auth()->user()->username;

        $data = Planningdokter::find($request->id);
        if (!$data) {
            return new JsonResponse(['message' => 'Data planning tidak ditemukan'], 404);
        }

        $data->verif_dpjp = 1;
        $data->tgl_verif_dpjp = date('Y-m-d H:i:s');
        $data->user_verif_dpjp = $userKdPeg;
        $data->save();

        return new JsonResponse($data);
    }
}
