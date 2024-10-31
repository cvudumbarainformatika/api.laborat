<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Anamnesis\AnamnesisBps;
use App\Models\Simrs\Anamnesis\AnamnesisNips;
use App\Models\Simrs\Anamnesis\AnamnesisTambahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            try{
                DB::beginTransaction();
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
                        'kdruang' => 'POL014',
                        'user'  => $kdpegsimrs,
                    ]
                );

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
                        'sebutkankomunaksilainnya' => $request->sebutkankomunaksilainnya,
                        'penerjemah' => $request->penerjemah,
                        'sebutkanpenerjemah' => $request->sebutkanpenerjemah,
                        'bahasa_isyarat' => $request->bahasaisyarat,
                        'hambatan' => $request->hamabatan,
                        'sebutkanhambatan' => $request->sebutkanhambatan,
                        'riwayat_demam' => $request->riwayatdemam,
                        'berkeringat_malam_hari' => $request->berkeringat,
                        'riwayat_bepergian' => $request->riwayatbepergian,
                        'riwayat_pemakaian_obat' => $request->obatjangkapanjang,
                        'riwayat_bb_turun' => $request->bbturun,
                        'kdruang' => 'POL014',
                        'user' => $kdpegsimrs,
                    ]
                );

                if($request->metode === 'bps')
                {
                    $simpanbps = AnamnesisBps::create(
                        [
                            'noreg' => $request->noreg,
                            'norm' => $request->norm,
                            'id_heder' => $simpananamnesis->id,
                            'ekspresi_wajah' => $request->ekspresiwajah,
                            'gerakan_tangan' => $request->gerakantangan,
                            'kepatuhan_ventilasi_mekanik' => $request->kepatuhanventilasimekanik,
                            'skor' => $request->scroebps,
                            'keterangan_skor' => $request->ketscorebps,
                            'ruangan' => 'POL014',
                            'user' => $kdpegsimrs,

                        ]
                    );
                }

                if($request->metode === 'nips')
                {
                    $simpannips = AnamnesisNips::create(
                        [
                            'noreg' => $request->noreg,
                            'norm' => $request->norm,
                            'id_heder' => $simpananamnesis->id,
                            'ekspresi_wajah' => $request->ekspresiwajahnips,
                            'menangis' => $request->menangis,
                            'pola_nafas' => $request->polanafas,
                            'lengan' => $request->lengan,
                            'kaki' => $request->kaki,
                            'keadaan_rangsangan' => $request->keadaanrangsangan,
                            'skor' => $request->scroenips,
                            'ket_skor' => $request->ketscorenips,
                            'ruangan' => 'POL014',
                            'user' => $kdpegsimrs,

                        ]
                    );
                }

                $hasil = Anamnesis::with(
                    [
                        'anamnesetambahan','anamnesebps','anamnesenips'
                    ]
                )->where('rs1', $request->noreg)
                ->where('kdruang', 'POL014')
                ->limit(1)
                ->orderBy('id','Desc')
                ->get();

                DB::commit();

                return new JsonResponse([
                    'message' => 'BERHASIL DISIMPAN',
                    'result' => $hasil
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return new JsonResponse(['message' => 'ada kesalahan', 'error' => $e], 500);
            }
        }
    }

    public function hapusanamnesis(Request $request)
    {

        try{
            DB::beginTransaction();
            $dataanamnesis = Anamnesis::where('id', $request->id);
            $dataanamnesistambahan = AnamnesisTambahan::where('id_heder', $request->id);
            $dataanamnesisbps = AnamnesisBps::where('id_heder', $request->id);
            $dataanamnesisnips = AnamnesisNips::where('id_heder', $request->id);

            $hapusanamnesis = $dataanamnesis->delete();
            $hapusanamnesistambahan = $dataanamnesistambahan->delete();
            $hapusanamnesisbps = $dataanamnesisbps->delete();
            $hapusanamnesisnips = $dataanamnesisnips->delete();

            $hasil = Anamnesis::with(
                [
                    'anamnesetambahan','anamnesebps','anamnesenips'
                ]
            )->where('rs1', $request->noreg)
            ->where('kdruang', 'POL014')
            ->limit(1)
            ->orderBy('id','Desc')
            ->get();

            DB::commit();

            return new JsonResponse([
                'message' => 'BERHASIL DIHAPUS',
                'result' => $hasil
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
}
