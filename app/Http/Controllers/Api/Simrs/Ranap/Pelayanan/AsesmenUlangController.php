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

        return new JsonResponse([
            'jatuh' => $jatuh,
            'nyeri' => $nyeri
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
}
