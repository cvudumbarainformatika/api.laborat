<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Ranap\Pelayanan\AsesmenUlangJatuh;
use App\Models\Simrs\Ranap\Pelayanan\AsesmenUlangNyeri;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\Penilaian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsesmenUlangController extends Controller
{
    public function index(Request $request)
    {
        $noreg = $request->noreg;

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
            // 1. Simpan ke tabel riwayat asesmen ulang jatuh
            $jatuh = new AsesmenUlangJatuh();
            $jatuh->fill($request->all());
            $jatuh->kdpegsimrs = $kdpegsimrs;
            $jatuh->save();

            // 2. OPSI 2: Sinkronisasikan ke tabel penilaians agar list pengunjung/stiker kuning terupdate
            $penilaian = Penilaian::where('rs1', $request->noreg)->first();
            $kuningVal = ($request->kuning == 1 || $request->kuning === true);

            // Susun data JSON yang sesuai dengan struktur inputan awal di frontend
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

                Penilaian::where('id', $penilaian->id)->update($updatePayload);
            } else {
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

                Penilaian::create($insertPayload);
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

    public function simpanNyeri(Request $request)
    {
        $kdpegsimrs = auth()->user()->pegawai->kdpegsimrs ?? null;

        try {
            $nyeri = new AsesmenUlangNyeri();
            $nyeri->fill($request->all());
            $nyeri->kdpegsimrs = $kdpegsimrs;
            $nyeri->save();

            return new JsonResponse([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'result' => $nyeri
            ], 200);
        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Gagal menyimpan data',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
