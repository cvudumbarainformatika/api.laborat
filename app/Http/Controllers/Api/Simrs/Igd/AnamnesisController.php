<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Anamnesis\AnamnesisTambahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnamnesisController extends Controller
{
    public function simpananamnesis(Request $request)
    {
        // return ('wew');
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;

        if ($request->has('id')) {
            $hasil = Anamnesis::where('id', $request->id)->update(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => date('Y-m-d H:i:s'),
                    'rs4' => $request->keluhanutama,
                    'riwayatpenyakit' => $request->riwayatpenyakit ?? '',
                    'riwayatalergi' => $request->riwayatalergi ?? '',
                    'keteranganalergi' => $request->keteranganalergi ?? '',
                    'riwayatpengobatan' => $request->riwayatpengobatan ?? '',
                    'riwayatpenyakitsekarang' => $request->riwayatpenyakitsekarang ?? '',
                    'riwayatpenyakitkeluarga' => $request->riwayatpenyakitkeluarga ?? '',
                    'skreeninggizi' => $request->skreeninggizi ?? 0,
                    'asupanmakan' => $request->asupanmakan ?? 0,
                    'kondisikhusus' => $request->kondisikhusus ?? '',
                    'skor' => $request->skor ?? 0,
                    'scorenyeri' => $request->skornyeri ?? 0,
                    'keteranganscorenyeri' => $request->keteranganscorenyeri ?? '',
                    //    'keteranganscorenyeri' => $request->riwayatpekerjaan ?? '',
                    'user'  => $kdpegsimrs,
                ]
            );
            if ($hasil === 1) {
                $simpananamnesis = Anamnesis::where('id', $request->id)->first();
            } else {
                $simpananamnesis = null;
            }
        } else {
            $simpananamnesis = Anamnesis::create(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => date('Y-m-d H:i:s'),
                    'rs4' => $request->keluhanutama,
                    'riwayatpenyakit' => $request->riwayatpenyakit ?? '',
                    'riwayatalergi' => $request->riwayatalergi ?? '',
                    'keteranganalergi' => $request->keteranganalergi ?? '',
                    'riwayatpengobatan' => $request->riwayatpengobatan ?? '',
                    'riwayatpenyakitsekarang' => $request->riwayatpenyakitsekarang ?? '',
                    'riwayatpenyakitkeluarga' => $request->riwayatpenyakitkeluarga ?? '',
                    'skreeninggizi' => $request->skreeninggizi ?? 0,
                    'asupanmakan' => $request->asupanmakan ?? 0,
                    'kondisikhusus' => $request->kondisikhusus ?? '',
                    'skor' => $request->skor ?? 0,
                    'scorenyeri' => $request->skornyeri ?? 0,
                    'keteranganscorenyeri' => $request->keteranganscorenyeri ?? '',
                    'user'  => $kdpegsimrs,
                ]
            );
        }
        if (!$simpananamnesis) {
            return new JsonResponse(['message' => 'GAGAL DISIMPAN'], 500);
        }

        $simpansambungan = AnamnesisTambahan::create(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'id_heder' => $simpananamnesis->id,
                'lokasi_nyeri' => $request->lokasinyeri,
                'durasi_nyeri' => $request->durasinyeri,
                'penyebab_nyeri' => $request->penyebabnyeri,
                'frekwensi_nyeri' => $request->frekwensinyeri,
                'nyeri_hilang' => $request->nyerihilang,
                'sebutkannyerihilang' => $request->sebutkannyerihilang,
                'aktifitas_mobilitas' => $request->aktivitasmobilitas,
                'sebutkanperlubanuan' => $request->sebutkanperlubanuan,
                'alat_bantu_jalan' => $request->aktivitasAlatBnatujalan,
                'sebutkanalatbantujalan' => $request->sebutkanalatbantujalan,
                'bicara' => $request->kebutuhankomunikasidanedukasi,
                'sebutkankomunakasilainya' => $request->sebutkankomunaksilainnya,
                'penerjemah' => $request->penerjemah,
                'sebutkanpenerjemah' => $request->sebutkanpenerjemah,
                'bahasa_isyarat' => $request->bahasaisyarat,
                'hambatan' => $request->hamabatan,
                'sebutkanhambatan' => $request->id,
                'riwayat_demam' => $request->id,
                'berkeringat_malam_hari' => $request->id,
                'riwayat_bepergian' => $request->id,
                'riwayat_pemakaian_obat' => $request->id,
                'riwayat_bb_turun' => $request->id,
                'kdruang' => 'POL014',
                'user' => $request->id,

            ]
        );

        return new JsonResponse([
            'message' => 'BERHASIL DISIMPAN',
            'result' => $simpananamnesis
        ], 200);
    }
}
