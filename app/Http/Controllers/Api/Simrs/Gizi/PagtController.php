<?php

namespace App\Http\Controllers\Api\Simrs\Gizi;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Hais\HaisTrans;
use App\Models\Simrs\Master\Mhais;
use App\Models\Simrs\Penunjang\Gizi\PagtGizi;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PagtController extends Controller
{

    public function list()
    {
      $data = PagtGizi::with('petugas')->where('noreg', 'Like', '%' . request('noreg') . '%')->get();
      return response()->json($data);
    }

    public function simpan(Request $request)
    {

        $user = FormatingHelper::session_user();
        $data = $request->all();

        $gizi = PagtGizi::updateOrCreate(

            [
                'noreg'=> $data['noreg'],
                'norm'=> $data['norm'],
                'kdruang'=> $data['kdruang']
            ],

            [
                'antropometri'        => $data['antropometri'] ?? null,
                'status_gizi'         => $data['status_gizi'] ?? null,
                'biokimia'            => $data['biokimia'] ?? null,
                'biokimia_ket'        => $data['biokimiaKet'] ?? null,
                'klinis'              => $data['klinis'] ?? null,
                'klinis_ket'          => $data['klinisKet'] ?? null,
                'alergi_makanan'      => $data['alergiMakanan'] ?? null,
                'alergi_makanan_ket'  => $data['alergiMakananKet'] ?? null,
                'pola_makan'          => $data['polaMakan'] ?? null,
                'pola_makan_ket'      => $data['polaMakanKet'] ?? null,
                'lain_lain'           => $data['lainlain'] ?? null,
                'nafsu_makan'         => $data['nafsuMakan'] ?? null,
                'nafsu_makan_ket'     => $data['nafsuMakanKet'] ?? null,
                'sulit_nelan'         => $data['sulitNelan'] ?? null,
                'sulit_nelan_ket'     => $data['sulitNelanKet'] ?? null,
                'sulit_ngunyah'       => $data['sulitNgunyah'] ?? null,
                'sulit_ngunyah_ket'   => $data['sulitNgunyahKet'] ?? null,
                'mual'                => $data['mual'] ?? null,
                'mual_ket'            => $data['mualKet'] ?? null,
                'rw_peny_dhl'         => $data['rwPenyDhl'] ?? null,
                'rw_peny_dhl_ket'     => $data['rwPenyDhlKet'] ?? null,
                'rw_peny_skr'         => $data['rwPenySkr'] ?? null,
                'user_id'             => $user['kodesimrs'] ?? null
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Data gizi berhasil disimpan',
            'result' => $gizi->load('petugas')
        ]);
    }
}
