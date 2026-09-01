<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Ranap\Pelayanan\AsesmenUlangJatuh;
use App\Models\Simrs\Ranap\Pelayanan\AsesmenUlangNyeri;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\Penilaian;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Anamnesis\KeluhanNyeri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsesmenUlangController extends Controller
{
    public function index(Request $request)
    {
        $noreg = $request->noreg; // by noreg

        $jatuh = AsesmenUlangJatuh::where('noreg', $noreg)
            ->with('pegawai:kdpegsimrs,nik,nama,kdgroupnakes')
            ->orderBy('created_at', 'DESC')
            ->get();

        $nyeri = AsesmenUlangNyeri::where('noreg', $noreg)
            ->with('pegawai:kdpegsimrs,nik,nama,kdgroupnakes')
            ->orderBy('created_at', 'DESC')
            ->get();

        $pascaJatuh = \App\Models\Simrs\Ranap\Pelayanan\AsesmenPascaJatuh::where('noreg', $noreg)
            ->with('pegawai:kdpegsimrs,nik,nama,kdgroupnakes')
            ->orderBy('created_at', 'DESC')
            ->get();

        $penyakitMenular = DB::table('asesmen_penyakit_menular')
            ->where('noreg', $noreg)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->map(function ($item) {
                $item->cara_penularan = $item->cara_penularan ? json_decode($item->cara_penularan, true) : [];
                $item->apd = $item->apd ? json_decode($item->apd, true) : [];
                return $item;
            });

        $monitoringRestrain = DB::table('monitoring_restrain')
            ->where('noreg', $noreg)
            ->orderBy('tanggal', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->get()
            ->map(function ($item) {
                $item->tanda_cedera = $item->tanda_cedera ? json_decode($item->tanda_cedera, true) : [];
                $item->higiene = $item->higiene ? json_decode($item->higiene, true) : [];
                return $item;
            });

        return new JsonResponse([
            'jatuh' => $jatuh,
            'nyeri' => $nyeri,
            'pasca_jatuh' => $pascaJatuh,
            'penyakit_menular' => $penyakitMenular,
            'monitoring_restrain' => $monitoringRestrain
        ], 200);
    }

    public function simpanJatuh(Request $request)
    {
        $kdpegsimrs = auth()->user()->pegawai->kdpegsimrs ?? null;
        $pegawai = auth()->user()->pegawai;

        DB::beginTransaction();
        try {
            // 1. Susun data JSON yang sesuai dengan struktur inputan awal di frontend
            $kuningVal = ($request->kuning == 1 || $request->kuning === true);
            $skorKey = 'skor' . ucfirst($request->metode); // skorHumpty, skorMorse, atau skorOntario
            $syncData = [
                $skorKey => [
                    'skor' => $request->skor,
                    'label' => $request->kategori,
                    'kuning' => $kuningVal
                ]
            ];

            // Masukkan rincian jawaban skoring ke dalam struktur JSON
            if ($request->details) {
                foreach ($request->details as $key => $val) {
                    $syncData[$key] = [
                        'label' => $val['value'] ?? '',
                        'skor' => $val['skor'] ?? 0
                    ];
                }
            }

            $syncJson = json_encode($syncData);

            // Cek apakah ini UPDATE (edit) atau CREATE (baru)
            if ($request->id) {
                // UPDATE OPERATION
                $jatuh = AsesmenUlangJatuh::find($request->id);
                if (!$jatuh) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Data riwayat tidak ditemukan'
                    ], 404);
                }

                // Update Penilaian terkait
                if ($jatuh->penilaian_id) {
                    $penilaian = Penilaian::find($jatuh->penilaian_id);
                    if ($penilaian) {
                        $updatePayload = [
                            'rs3' => date('Y-m-d H:i:s'),
                            'user' => $kdpegsimrs,
                            'group_nakes' => $pegawai ? $pegawai->kdgroupnakes : null
                        ];

                        if ($request->metode === 'humpty') {
                            $updatePayload['humpty_dumpty'] = $syncJson;
                        } elseif ($request->metode === 'morse') {
                            $updatePayload['morse_fall'] = $syncJson;
                        } elseif ($request->metode === 'ontario') {
                            $updatePayload['ontario'] = $syncJson;
                        }

                        $penilaian->update($updatePayload);
                    }
                }

                // Update AsesmenUlangJatuh
                $jatuh->fill($request->all());
                $jatuh->save();
            } else {
                // CREATE OPERATION
                // 2. Buat baris Penilaian baru (pola seperti CPPT)
                $insertPayload = [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => date('Y-m-d H:i:s'),
                    'kdruang' => $request->kdruang,
                    'user' => $kdpegsimrs,
                    'group_nakes' => $pegawai ? $pegawai->kdgroupnakes : null
                ];

                if ($request->metode === 'humpty') {
                    $insertPayload['humpty_dumpty'] = $syncJson;
                } elseif ($request->metode === 'morse') {
                    $insertPayload['morse_fall'] = $syncJson;
                } elseif ($request->metode === 'ontario') {
                    $insertPayload['ontario'] = $syncJson;
                }

                $newPenilaian = Penilaian::create($insertPayload);

                // 3. Simpan ke tabel riwayat asesmen ulang jatuh, hubungkan dengan penilaian_id
                $jatuh = new AsesmenUlangJatuh();
                $jatuh->fill($request->all());
                $jatuh->kdpegsimrs = $kdpegsimrs;
                $jatuh->penilaian_id = $newPenilaian->id;
                $jatuh->save();
            }

            DB::commit();

            return new JsonResponse([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'result' => $jatuh
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new JsonResponse([
                'success' => false,
                'message' => 'Gagal menyimpan data',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function hapusJatuh(Request $request)
    {
        $id = $request->id;
        $jatuh = AsesmenUlangJatuh::find($id);
        if (!$jatuh) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Hapus penilaian terkait jika ada
            if ($jatuh->penilaian_id) {
                Penilaian::where('id', $jatuh->penilaian_id)->delete();
            }

            // Hapus data riwayat asesmen ulang jatuh
            $jatuh->delete();

            DB::commit();

            return new JsonResponse([
                'success' => true,
                'message' => 'Data berhasil dihapus'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new JsonResponse([
                'success' => false,
                'message' => 'Gagal menghapus data',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function simpanNyeri(Request $request)
    {
        $kdpegsimrs = auth()->user()->pegawai->kdpegsimrs ?? null;
        $pegawai = auth()->user()->pegawai;

        DB::beginTransaction();
        try {
            // 1. Susun JSON data keluhan nyeri untuk dimasukkan ke rs209_nyeri
            $syncNyeri = [
                'skorNyeri' => $request->skor,
                'ket' => $request->ket,
            ];
            if ($request->details) {
                foreach ($request->details as $key => $val) {
                    $syncNyeri[$key] = [
                        'text' => $val['value'] ?? '',
                        'skor' => $val['skor'] ?? 0
                    ];
                }
            }

            if ($request->id) {
                // UPDATE OPERATION (EDIT)
                $nyeri = AsesmenUlangNyeri::find($request->id);
                if (!$nyeri) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Data riwayat tidak ditemukan'
                    ], 404);
                }

                // Update Anamnesis & KeluhanNyeri terkait jika ada
                if ($nyeri->rs209_id) {
                    $anamnesis = Anamnesis::find($nyeri->rs209_id);
                    if ($anamnesis) {
                        $anamnesis->update([
                            'rs3' => date('Y-m-d H:i:s'),
                            'user' => $kdpegsimrs
                        ]);
                    }

                    $klNyeri = KeluhanNyeri::where('rs209_id', $nyeri->rs209_id)->first();
                    if ($klNyeri) {
                        $updateNyeri = [
                            'skor' => $request->skor,
                            'keluhan' => $request->ket,
                            'user_input' => $kdpegsimrs,
                            'group_nakes' => $pegawai ? $pegawai->kdgroupnakes : null
                        ];

                        if ($request->metode === 'Neonatal Infant Pain Scale (NIPS)') {
                            $updateNyeri['neonatal'] = $syncNyeri;
                            $updateNyeri['dewasa'] = null;
                        } else {
                            $updateNyeri['dewasa'] = $syncNyeri;
                            $updateNyeri['neonatal'] = null;
                        }

                        $klNyeri->update($updateNyeri);
                    }
                }

                // Update AsesmenUlangNyeri
                $nyeri->fill($request->all());
                $nyeri->save();
            } else {
                // CREATE OPERATION (BARU)
                // 2. Buat baris Anamnesis (rs209) baru
                $anamnesis = Anamnesis::create([
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => date('Y-m-d H:i:s'),
                    'kdruang' => $request->kdruang,
                    'user' => $kdpegsimrs,
                    'awal' => '0'
                ]);

                // 3. Buat baris KeluhanNyeri (rs209_nyeri) baru
                $insertNyeri = [
                    'rs209_id' => $anamnesis->id,
                    'noreg' => $request->noreg,
                    'norm' => $request->norm,
                    'skor' => $request->skor,
                    'keluhan' => $request->ket,
                    'user_input' => $kdpegsimrs,
                    'group_nakes' => $pegawai ? $pegawai->kdgroupnakes : null
                ];

                if ($request->metode === 'Neonatal Infant Pain Scale (NIPS)') {
                    $insertNyeri['neonatal'] = $syncNyeri;
                } else {
                    $insertNyeri['dewasa'] = $syncNyeri;
                }

                KeluhanNyeri::create($insertNyeri);

                // 4. Simpan ke tabel riwayat asesmen ulang nyeri dengan rs209_id
                $nyeri = new AsesmenUlangNyeri();
                $nyeri->fill($request->all());
                $nyeri->kdpegsimrs = $kdpegsimrs;
                $nyeri->rs209_id = $anamnesis->id;
                $nyeri->save();
            }

            DB::commit();

            return new JsonResponse([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'result' => $nyeri
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new JsonResponse([
                'success' => false,
                'message' => 'Gagal menyimpan data',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function hapusNyeri(Request $request)
    {
        $id = $request->id;
        $nyeri = AsesmenUlangNyeri::find($id);
        if (!$nyeri) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Hapus anamnesis & keluhan nyeri terkait jika ada
            if ($nyeri->rs209_id) {
                KeluhanNyeri::where('rs209_id', $nyeri->rs209_id)->delete();
                Anamnesis::where('id', $nyeri->rs209_id)->delete();
            }

            // Hapus data riwayat asesmen ulang nyeri
            $nyeri->delete();

            DB::commit();

            return new JsonResponse([
                'success' => true,
                'message' => 'Data berhasil dihapus'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new JsonResponse([
                'success' => false,
                'message' => 'Gagal menghapus data',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function simpanPascaJatuh(Request $request)
    {
        $kdpegsimrs = auth()->user()->pegawai->kdpegsimrs ?? null;

        DB::beginTransaction();
        try {
            if ($request->id) {
                $pasca = \App\Models\Simrs\Ranap\Pelayanan\AsesmenPascaJatuh::find($request->id);
                if (!$pasca) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Data tidak ditemukan'
                    ], 404);
                }
                $pasca->fill($request->all());
                $pasca->save();
            } else {
                $pasca = new \App\Models\Simrs\Ranap\Pelayanan\AsesmenPascaJatuh();
                $pasca->fill($request->all());
                $pasca->kdpegsimrs = $kdpegsimrs;
                $pasca->save();
            }

            DB::commit();

            return new JsonResponse([
                'success' => true,
                'message' => 'Data monitoring pasca jatuh berhasil disimpan',
                'result' => $pasca
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new JsonResponse([
                'success' => false,
                'message' => 'Gagal menyimpan data',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function hapusPascaJatuh(Request $request)
    {
        $id = $request->id;
        $pasca = \App\Models\Simrs\Ranap\Pelayanan\AsesmenPascaJatuh::find($id);
        if (!$pasca) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        try {
            $pasca->delete();
            return new JsonResponse([
                'success' => true,
                'message' => 'Data berhasil dihapus'
            ], 200);
        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Gagal menghapus data',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function simpanPenyakitMenular(Request $request)
    {
        $kdpegsimrs = auth()->user()->pegawai->kdpegsimrs ?? $request->kdpegsimrs;
        $petugas = auth()->user()->pegawai->nama ?? $request->petugas;

        $data = [
            'noreg' => $request->noreg,
            'norm' => $request->norm,
            'kdruangan' => $request->kdruangan,
            'kdpegsimrs' => $kdpegsimrs,
            'petugas' => $petugas,
            'sumber' => $request->sumber ?? 'ranap',

            // Section A
            'diagnosis' => $request->diagnosis,
            'status_diag' => $request->status_diag ?? 'baru',
            'lama_sejak' => $request->lama_sejak,
            'tahu_penyakit' => $request->tahu_penyakit ?? 'tahu',
            'sumber_info' => $request->sumber_info ?? 'dokter',
            'info_jangka' => $request->info_jangka ?? 'tidak',
            'durasi_pengobatan' => $request->durasi_pengobatan,
            'pemeriksaan_rutin' => $request->pemeriksaan_rutin ?? 'tidak',
            'tempat_rutin' => $request->tempat_rutin,
            'cara_penularan' => is_array($request->cara_penularan) ? json_encode($request->cara_penularan) : null,
            'ruang_isolasi' => $request->ruang_isolasi ?? 'tidak',
            'ruang_isolasi_ket' => $request->ruang_isolasi_ket,
            'rujuk_ke' => $request->rujuk_ke,
            'pakai_apd' => $request->pakai_apd ?? 'tidak',
            'apd' => is_array($request->apd) ? json_encode($request->apd) : null,
            'penyakit_penyerta' => $request->penyakit_penyerta ?? 'tidak',
            'ket_penyakit_penyerta' => $request->ket_penyakit_penyerta,

            // Section B
            'b_tahu_penyakit' => $request->b_tahu_penyakit ?? 'tahu',
            'b_sumber_info' => $request->b_sumber_info ?? 'dokter',
            'b_info_jangka' => $request->b_info_jangka ?? 'tidak',
            'b_durasi_pengobatan' => $request->b_durasi_pengobatan,
            'b_pemeriksaan_rutin' => $request->b_pemeriksaan_rutin ?? 'tidak',
            'b_tempat_rutin' => $request->b_tempat_rutin,
            'b_dirawat_terpisah' => $request->b_dirawat_terpisah ?? 'tidak',
            'b_tempat_terpisah' => $request->b_tempat_terpisah,

            // Section C & D
            'analisa_masalah' => $request->analisa_masalah,
            'tindakan' => $request->tindakan,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        DB::beginTransaction();
        try {
            if ($request->filled('id')) {
                DB::table('asesmen_penyakit_menular')
                    ->where('id', $request->id)
                    ->update($data);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                DB::table('asesmen_penyakit_menular')->insert($data);
            }

            DB::commit();
            return new JsonResponse([
                'success' => true,
                'message' => 'Data Asesmen Penyakit Menular Berhasil Disimpan'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new JsonResponse([
                'success' => false,
                'message' => 'Gagal menyimpan data',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function hapusPenyakitMenular(Request $request)
    {
        $id = $request->id;
        try {
            DB::table('asesmen_penyakit_menular')->where('id', $id)->delete();
            return new JsonResponse([
                'success' => true,
                'message' => 'Data berhasil dihapus'
            ], 200);
        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Gagal menghapus data',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function simpanMonitoringRestrain(Request $request)
    {
        $kdpegsimrs = auth()->user()->pegawai->kdpegsimrs ?? $request->kdpegsimrs;
        $petugas = auth()->user()->pegawai->nama ?? $request->petugas;

        $data = [
            'noreg' => $request->noreg,
            'norm' => $request->norm,
            'kdruangan' => $request->kdruangan,
            'kdpegsimrs' => $kdpegsimrs,
            'petugas' => $petugas,
            'sumber' => $request->sumber ?? 'ranap',
            'tanggal' => $request->tanggal ?? date('Y-m-d H:i:s'),

            // TTV
            'ttv_t' => $request->ttv_t,
            'ttv_n' => $request->ttv_n,
            'ttv_s' => $request->ttv_s,
            'ttv_rr' => $request->ttv_rr,
            'ttv_crt' => $request->ttv_crt,
            'ttv_akral' => $request->ttv_akral,

            // Tanda Cedera Akibat Fiksasi (Array JSON)
            'tanda_cedera' => is_array($request->tanda_cedera) ? json_encode($request->tanda_cedera) : null,

            // Nutrisi
            'nutrisi_makan' => $request->nutrisi_makan,
            'nutrisi_minum_gelas' => $request->nutrisi_minum_gelas,
            'nutrisi_minum_cc' => $request->nutrisi_minum_cc,

            // Mobilisasi Tempat Fiksasi
            'mobilisasi' => $request->mobilisasi,
            'mobilisasi_tiap_jam' => $request->mobilisasi_tiap_jam,

            // Higiene
            'higiene' => is_array($request->higiene) ? json_encode($request->higiene) : null,
            'higiene_mandi_x' => $request->higiene_mandi_x,
            'higiene_oral_x' => $request->higiene_oral_x,

            // Eliminasi
            'eliminasi_bab_x' => $request->eliminasi_bab_x,
            'eliminasi_bak_x' => $request->eliminasi_bak_x,

            // Kesadaran
            'kesadaran' => $request->kesadaran,

            'updated_at' => date('Y-m-d H:i:s')
        ];

        DB::beginTransaction();
        try {
            if ($request->filled('id')) {
                DB::table('monitoring_restrain')
                    ->where('id', $request->id)
                    ->update($data);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                DB::table('monitoring_restrain')->insert($data);
            }

            DB::commit();
            return new JsonResponse([
                'success' => true,
                'message' => 'Data Monitoring Pengikatan Restrain Berhasil Disimpan'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new JsonResponse([
                'success' => false,
                'message' => 'Gagal menyimpan data monitoring restrain',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function hapusMonitoringRestrain(Request $request)
    {
        $id = $request->id;
        try {
            DB::table('monitoring_restrain')->where('id', $id)->delete();
            return new JsonResponse([
                'success' => true,
                'message' => 'Data monitoring restrain berhasil dihapus'
            ], 200);
        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Gagal menghapus data',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
