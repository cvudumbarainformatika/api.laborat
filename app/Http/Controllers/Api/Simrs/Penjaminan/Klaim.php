<?php

namespace App\Http\Controllers\Api\Simrs\Penjaminan;

use App\Http\Controllers\Controller;
use App\Helpers\Eklaim\Eklaim;
use App\Http\Requests\GroupIdrgRequest;
use App\Http\Requests\StoreIdrgDiagnosaRequest;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Penjaminan\listcasmixrajal;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
<<<<<<< HEAD
use Illuminate\Support\Facades\Schema;
=======
use Illuminate\Support\Facades\Log;
>>>>>>> 5ed42ad2d44f9176d0da048d0ef6ec0727a47565

class Klaim extends Controller
{
    public function caraMasuk(): JsonResponse
    {
        $data = DB::table('cara_masuk')
            ->select('kode', 'keterangan')
            ->orderBy('kode')
            ->get();

        return new JsonResponse($data);
    }

    public function cariDiagnosaIdrg(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'term' => ['required', 'string', 'min:2', 'max:100'],
        ]);
        $term = trim($validated['term']);
        $kodeTanpaTitik = str_replace('.', '', $term);

        $diagnosaMaster = DB::table('diagnosa_master')
            ->selectRaw('icd as nilai, diagnosa as keterangan')
            ->where(function ($query) use ($term, $kodeTanpaTitik) {
                $query->where('icd', 'like', $term.'%')
                    ->orWhereRaw("REPLACE(icd, '.', '') LIKE ?", [$kodeTanpaTitik.'%'])
                    ->orWhere('diagnosa', 'like', '%'.$term.'%');
            });

        $codeSystemIdrg = DB::table('codesystemidrg')
            ->selectRaw('code as nilai, description as keterangan')
            ->where('system', 'ICD_10_2010_IM')
            ->where(function ($query) use ($term) {
                $query->where('code', 'like', $term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%');
            });

        $data = DB::query()
            ->fromSub($diagnosaMaster->unionAll($codeSystemIdrg), 'hasil')
            ->select('nilai')
            ->selectRaw('MAX(keterangan) as keterangan')
            ->groupBy('nilai')
            ->orderBy('nilai')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'label' => $item->nilai.' ('.$item->keterangan.')',
                'diagnosa' => $item->keterangan,
                'value' => $item->nilai,
            ]);

        return new JsonResponse($data);
    }

<<<<<<< HEAD
    public function simpanHasilGroupingIdrg(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noreg' => ['required', 'string'],
            'hasil' => ['required', 'array'],
            'drug' => ['nullable', 'string'],
            'procedure' => ['nullable', 'string'],
            'prosthesis' => ['nullable', 'string'],
            'investigation' => ['nullable', 'string'],
        ]);

        $noreg = trim($validated['noreg']);
        $hasil = $validated['hasil']['idrg_klaim']
            ?? $validated['hasil']['grouping']
            ?? $validated['hasil']['response']
            ?? $validated['hasil'];
        if (is_array($hasil)) {
            $hasil = $hasil['response_idrg']
                ?? $hasil['response']['response_idrg']
                ?? $hasil['response']['data']
                ?? $hasil;
        }
        if (!is_array($hasil)) {
            return new JsonResponse(['success' => false, 'message' => 'Respons grouping iDRG tidak valid'], 422);
        }

        $kolomDiizinkan = [
            'script_version', 'logic_version', 'mdc_description', 'mdc_number',
            'drg_description', 'drg_code', 'cost_weight', 'nbr', 'total_cost_weight',
            'total_tarif', 'total_klaim', 'status_cd', 'special_cmg_option_code', 'opt_cmg',
            'topup_drug_code', 'topup_drug_cost_weight', 'topup_procedure_code',
            'topup_procedure_cost_weight', 'topup_prosthesis_code',
            'topup_prosthesis_cost_weight', 'topup_investigation_code',
            'topup_investigation_cost_weight',
        ];
        $data = array_intersect_key($hasil, array_flip($kolomDiizinkan));
        foreach (['drug', 'procedure', 'prosthesis', 'investigation'] as $field) {
            $data[$field.'_opt'] = $validated[$field] ?? '';
        }
        $data = array_intersect_key($data, array_flip(Schema::getColumnListing('idrg_klaim')));

        $updated = DB::table('idrg_klaim')->where('noreg', $noreg)->update($data);
        if (!$updated && !DB::table('idrg_klaim')->where('noreg', $noreg)->exists()) {
            return new JsonResponse(['success' => false, 'message' => 'Data iDRG tidak ditemukan'], 404);
        }

        return new JsonResponse(['success' => true]);
=======
    public function cariProsedurIdrg(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'term' => ['required', 'string', 'min:2', 'max:100'],
        ]);
        $term = trim($validated['term']);
        $kodeTanpaTitik = str_replace('.', '', $term);

        $prosedurMaster = DB::table('prosedur_master')
            ->selectRaw('kd_prosedur as nilai, prosedur as keterangan')
            ->where(function ($query) use ($term, $kodeTanpaTitik) {
                $query->where('kd_prosedur', 'like', $term.'%')
                    ->orWhereRaw("REPLACE(kd_prosedur, '.', '') LIKE ?", [$kodeTanpaTitik.'%'])
                    ->orWhere('prosedur', 'like', '%'.$term.'%');
            });

        $codeSystemIdrg = DB::table('codesystemidrg')
            ->selectRaw('code as nilai, description as keterangan')
            ->where('system', 'ICD_9CM_2010_IM')
            ->where('validcode', 1)
            ->where(function ($query) use ($term, $kodeTanpaTitik) {
                $query->where('code', 'like', $term.'%')
                    ->orWhereRaw("REPLACE(code, '.', '') LIKE ?", [$kodeTanpaTitik.'%'])
                    ->orWhere('description', 'like', '%'.$term.'%');
            });

        $data = DB::query()
            ->fromSub($prosedurMaster->unionAll($codeSystemIdrg), 'hasil')
            ->select('nilai')
            ->selectRaw('MAX(keterangan) as keterangan')
            ->groupBy('nilai')
            ->orderBy('nilai')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'label' => $item->nilai.' ('.$item->keterangan.')',
                'prosedur' => $item->keterangan,
                'value' => $item->nilai,
            ]);

        return new JsonResponse($data);
    }

    public function simpanProsedurIdrg(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noreg' => ['required', 'string', 'max:100'],
            'kd_prosedur' => ['required', 'string', 'max:30'],
            'prosedur' => ['nullable', 'string', 'max:255'],
            'jumlah' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);
        $noreg = trim($validated['noreg']);
        $kode = trim($validated['kd_prosedur']);

        $isFinal = DB::table('klaim_trans_rajal')
            ->where('noreg', $noreg)
            ->where('delete_status', '')
            ->where('status_klaim', 'Final')
            ->exists()
            || DB::table('klaim_trans_ranap')
                ->where('noreg', $noreg)
                ->where('delete_status', '')
                ->where('status_klaim', 'Final')
                ->exists();
        if ($isFinal) {
            return new JsonResponse(['success' => false, 'message' => 'Maaf, klaim telah final.'], 422);
        }

        $master = DB::table('codesystemidrg')
            ->where('system', 'ICD_9CM_2010_IM')
            ->where('code', $kode)
            ->first(['validcode', 'description']);
        if (!$master || (string) $master->validcode !== '1') {
            return new JsonResponse(['success' => false, 'message' => 'Kode prosedur ini tidak bisa dipilih.'], 422);
        }

        if (DB::table('prosedur_klaim')->where('noreg', $noreg)->where('kd_prosedur', $kode)->exists()) {
            return new JsonResponse(['success' => false, 'message' => 'Prosedur tersebut telah dientri.'], 422);
        }

        DB::table('prosedur_klaim')->insert([
            'noreg' => $noreg,
            'kd_prosedur' => $kode,
            'prosedur' => $validated['prosedur'] ?? $master->description ?? $kode,
            'tgl_input' => now(),
            'jumlah' => $validated['jumlah'] ?? 1,
        ]);

        return new JsonResponse(['success' => true, 'message' => 'Prosedur berhasil disimpan.']);
    }

    public function ubahJumlahProsedurIdrg(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noreg' => ['required', 'string', 'max:100'],
            'kd_prosedur' => ['required', 'string', 'max:30'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:999'],
        ]);
        $updated = DB::table('prosedur_klaim')
            ->where('noreg', $validated['noreg'])
            ->where('kd_prosedur', $validated['kd_prosedur'])
            ->update(['jumlah' => $validated['jumlah']]);

        return new JsonResponse([
            'success' => $updated > 0,
            'message' => $updated > 0 ? 'Jumlah prosedur berhasil diperbarui.' : 'Prosedur tidak ditemukan.',
        ], $updated > 0 ? 200 : 404);
    }

    public function hapusProsedurIdrg(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noreg' => ['required', 'string', 'max:100'],
            'kd_prosedur' => ['required', 'string', 'max:30'],
        ]);
        $deleted = DB::table('prosedur_klaim')
            ->where('noreg', $validated['noreg'])
            ->where('kd_prosedur', $validated['kd_prosedur'])
            ->delete();

        return new JsonResponse([
            'success' => $deleted > 0,
            'message' => $deleted > 0 ? 'Prosedur berhasil dihapus.' : 'Prosedur tidak ditemukan.',
        ], $deleted > 0 ? 200 : 404);
    }

    public function simpanDiagnosaIdrg(StoreIdrgDiagnosaRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $noreg = trim($validated['noreg']);
        $icd = trim($validated['icd']);

        $isFinal = DB::table('klaim_trans_rajal')
            ->where('noreg', $noreg)
            ->where('delete_status', '')
            ->where('status_klaim', 'Final')
            ->exists()
            || DB::table('klaim_trans_ranap')
                ->where('noreg', $noreg)
                ->where('delete_status', '')
                ->where('status_klaim', 'Final')
                ->exists();

        if ($isFinal) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Maaf, klaim telah final.',
            ], 422);
        }

        if (DB::table('diagnosa_klaim')->where('noreg', $noreg)->where('icd', $icd)->exists()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Maaf, diagnosa tersebut telah dientri.',
            ], 422);
        }

        $master = DB::table('codesystemidrg')
            ->select('accpdx', 'validcode', 'description')
            ->where('code', $icd)
            ->first();

        if (!$master || (string) $master->validcode !== '1') {
            return new JsonResponse([
                'success' => false,
                'message' => 'ICD ini tidak Bisa Di pilih...!!!',
            ], 422);
        }

        $hasDiagnosis = DB::table('diagnosa_klaim')
            ->where('noreg', $noreg)
            ->exists();

        if ((string) $master->accpdx === 'N' && !$hasDiagnosis) {
            return new JsonResponse([
                'success' => false,
                'message' => 'ICD ini tidak Bisa jadi diagnosa Primary...!!!',
            ], 422);
        }

        DB::transaction(function () use ($noreg, $icd, $validated, $master) {
            DB::table('diagnosa_klaim')->insert([
                'noreg' => $noreg,
                'icd' => $icd,
                'diagnosa' => $validated['diagnosa'] ?? $master->description ?? '',
                'tgl_input' => now(),
            ]);
        });

        return new JsonResponse([
            'success' => true,
            'message' => 'Diagnosa berhasil disimpan',
        ]);
    }

    public function getDiagnosaIdrg(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noreg' => ['required', 'string', 'max:100'],
            'nomor_sep' => ['required', 'string', 'max:200'],
        ]);

        $noreg = trim($validated['noreg']);
        $nomorSep = trim($validated['nomor_sep']);
        $eklaimResponse = Eklaim::curl_func([
            'metadata' => ['method' => 'idrg_diagnosa_get'],
            'data' => ['nomor_sep' => $nomorSep],
        ]);

        if ((string) ($eklaimResponse['metadata']['code'] ?? '') !== '200') {
            return new JsonResponse($eklaimResponse, 422);
        }

        $responseData = $eklaimResponse['response_idrg']
            ?? $eklaimResponse['response']['response_idrg']
            ?? $eklaimResponse['data']['response_idrg']
            ?? $eklaimResponse['response']['data']
            ?? $eklaimResponse['data']
            ?? [];

        $diagnosisValues = collect();
        $collectDiagnosis = function ($value) use (&$collectDiagnosis, $diagnosisValues) {
            if (is_string($value)) {
                $diagnosisValues->push($value);
                return;
            }
            if (!is_array($value)) return;

            foreach (['diagnosa', 'diagnosis', 'diagnosa_idrg', 'diagnosa_inagrouper'] as $key) {
                if (array_key_exists($key, $value)) {
                    $candidate = $value[$key];
                    if (is_array($candidate)) {
                        foreach ($candidate as $item) {
                            $collectDiagnosis($item);
                        }
                    } else {
                        $collectDiagnosis($candidate);
                    }
                }
            }

            foreach (['code', 'kode', 'code_diagnosa', 'kode_diagnosa', 'icd'] as $codeKey) {
                if (isset($value[$codeKey]) && !is_array($value[$codeKey])) {
                    $diagnosisValues->push($value[$codeKey]);
                    break;
                }
            }

            foreach ($value as $key => $nested) {
                if (is_array($nested) && !in_array($key, [
                    'metadata', 'topup_options', 'procedure', 'procedures',
                ], true)) {
                    $collectDiagnosis($nested);
                }
            }
        };
        $collectDiagnosis($responseData);

        $codes = $diagnosisValues
            ->flatMap(fn ($value) => explode('#', (string) $value))
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values();

        $fromEklaim = $codes->isNotEmpty();
        if (!$fromEklaim) {
            $codes = DB::table('diagnosa_klaim')
                ->where('noreg', $noreg)
                ->orderBy('id')
                ->pluck('icd')
                ->map(fn ($code) => trim((string) $code))
                ->filter()
                ->unique()
                ->values();
        }

        if ($codes->isNotEmpty()) {
            $rows = $codes->map(function (string $code) use ($noreg) {
                $master = DB::table('codesystemidrg')
                    ->where('code', $code)
                    ->first(['description']);

                return [
                    'noreg' => $noreg,
                    'icd' => $code,
                    'diagnosa' => $master->description ?? $code,
                    'tgl_input' => now(),
                ];
            })->all();

            DB::transaction(function () use ($noreg, $rows) {
                DB::table('diagnosa_klaim')->where('noreg', $noreg)->delete();
                DB::table('diagnosa_klaim')->insert($rows);
            });
        }

        return new JsonResponse([
            'success' => true,
            'message' => $fromEklaim
                ? 'Diagnosa iDRG berhasil diambil dari E-Klaim.'
                : 'E-Klaim tidak mengembalikan diagnosis, ditampilkan dari data lokal.',
            'metadata' => $eklaimResponse['metadata'] ?? [],
            'diagnosa' => $codes->implode('#'),
            'items' => $codes->map(fn ($code) => [
                'kode' => $code,
                'nama' => DB::table('codesystemidrg')->where('code', $code)->value('description') ?? $code,
            ])->all(),
        ]);
    }

    public function getProsedurIdrg(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noreg' => ['required', 'string', 'max:100'],
            'nomor_sep' => ['required', 'string', 'max:200'],
        ]);

        $noreg = trim($validated['noreg']);
        $nomorSep = trim($validated['nomor_sep']);
        $eklaimResponse = Eklaim::curl_func([
            'metadata' => ['method' => 'idrg_procedure_get'],
            'data' => ['nomor_sep' => $nomorSep],
        ]);

        if ((string) ($eklaimResponse['metadata']['code'] ?? '') !== '200') {
            return new JsonResponse($eklaimResponse, 422);
        }

        $responseData = $eklaimResponse['response_idrg']
            ?? $eklaimResponse['response']['response_idrg']
            ?? $eklaimResponse['data']['response_idrg']
            ?? $eklaimResponse['response']['data']
            ?? $eklaimResponse['data']
            ?? [];
        $procedureValues = collect();
        $collectProcedure = function ($value) use (&$collectProcedure, $procedureValues) {
            if (is_string($value)) {
                $procedureValues->push($value);
                return;
            }
            if (!is_array($value)) return;

            foreach (['procedure', 'procedures', 'prosedur', 'prosedur_idrg', 'procedure_idrg'] as $key) {
                if (array_key_exists($key, $value)) {
                    $candidate = $value[$key];
                    if (is_array($candidate)) {
                        foreach ($candidate as $item) $collectProcedure($item);
                    } else {
                        $collectProcedure($candidate);
                    }
                }
            }

            foreach (['code', 'kode', 'code_procedure', 'kode_prosedur'] as $codeKey) {
                if (isset($value[$codeKey]) && !is_array($value[$codeKey])) {
                    $jumlah = $value['jumlah'] ?? $value['quantity'] ?? 1;
                    $procedureValues->push($value[$codeKey].((int) $jumlah > 1 ? '+'.(int) $jumlah : ''));
                    break;
                }
            }

            foreach ($value as $key => $nested) {
                if (is_array($nested) && !in_array($key, ['metadata', 'topup_options'], true)) {
                    $collectProcedure($nested);
                }
            }
        };
        $collectProcedure($responseData);

        $procedureCodes = $procedureValues
            ->flatMap(fn ($value) => explode('#', (string) $value))
            ->map(function ($value) {
                $parts = explode('+', trim((string) $value), 2);
                return [
                    'kode' => trim($parts[0]),
                    'jumlah' => isset($parts[1]) && (int) $parts[1] > 0 ? (int) $parts[1] : 1,
                ];
            })
            ->filter(fn ($item) => $item['kode'] !== '')
            ->unique(fn ($item) => $item['kode'])
            ->values();

        // E-Klaim is the source of truth for synchronization. If it returns
        // no procedure, remove stale local procedures instead of falling back
        // to them and showing codes that no longer exist in E-Klaim.
        $items = $procedureCodes->map(function ($item) use ($noreg) {
            $nama = DB::table('codesystemidrg')->where('system', 'ICD_9CM_2010_IM')
                ->where('code', $item['kode'])->value('description');
            $nama ??= DB::table('prosedur_master')->where('kd_prosedur', $item['kode'])->value('prosedur');
            $nama ??= $item['kode'];
            return ['kode' => $item['kode'], 'nama' => $nama, 'jumlah' => $item['jumlah']];
        })->values();

        DB::transaction(function () use ($noreg, $items) {
            DB::table('prosedur_klaim')->where('noreg', $noreg)->delete();
            if ($items->isNotEmpty()) {
                DB::table('prosedur_klaim')->insert($items->map(fn ($item) => [
                    'noreg' => $noreg,
                    'kd_prosedur' => $item['kode'],
                    'prosedur' => $item['nama'],
                    'jumlah' => $item['jumlah'],
                    'tgl_input' => now(),
                ])->all());
            }
        });

        return new JsonResponse([
            'success' => true,
            'message' => $items->isNotEmpty()
                ? 'Prosedur iDRG berhasil disinkronkan dari E-Klaim.'
                : 'E-Klaim tidak mengembalikan prosedur; data lokal dikosongkan.',
            'metadata' => $eklaimResponse['metadata'] ?? [],
            'procedure' => $items->map(fn ($item) => $item['kode'].($item['jumlah'] > 1 ? '+'.$item['jumlah'] : ''))->implode('#'),
            'items' => $items->all(),
        ]);
    }

    public function hapusDiagnosaIdrg(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noreg' => ['required', 'string', 'max:100'],
            'icd' => ['required', 'string', 'max:30'],
        ]);

        $isFinal = DB::table('klaim_trans_rajal')
            ->where('noreg', $validated['noreg'])
            ->where('delete_status', '')
            ->where('status_klaim', 'Final')
            ->exists();
        if ($isFinal) {
            return new JsonResponse(['success' => false, 'message' => 'Maaf, klaim telah final.'], 422);
        }

        $deleted = DB::table('diagnosa_klaim')
            ->where('noreg', $validated['noreg'])
            ->where('icd', $validated['icd'])
            ->delete();

        return new JsonResponse([
            'success' => $deleted > 0,
            'message' => $deleted > 0 ? 'Diagnosa berhasil dihapus.' : 'Diagnosa tidak ditemukan.',
        ], $deleted > 0 ? 200 : 404);
>>>>>>> 5ed42ad2d44f9176d0da048d0ef6ec0727a47565
    }


    public function newClaim(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noreg' => ['required', 'string', 'max:100'],
            'nomor_kartu' => ['required', 'string', 'max:200'],
            'nomor_sep' => ['nullable', 'string', 'max:200'],
            'nomor_rm' => ['required', 'string', 'max:100'],
            'nama_pasien' => ['required', 'string', 'max:200'],
            'tgl_lahir' => ['required', 'date'],
            'gender' => ['required', 'in:1,2'],
        ]);

        $noreg = trim($validated['noreg']);
        $nomorSep = trim((string) ($validated['nomor_sep'] ?? ''));

        $queryNewClaim = [
            'metadata' => ['method' => 'new_claim'],
            'data' => [
                'nomor_kartu' => $validated['nomor_kartu'],
                'nomor_sep' => $nomorSep,
                'nomor_rm' => $validated['nomor_rm'],
                'nama_pasien' => $validated['nama_pasien'],
                'tgl_lahir' => $validated['tgl_lahir'],
                'gender' => $validated['gender'],
            ],
        ];

        $eklaimResponse = Eklaim::curl_func($queryNewClaim);
        $responseCode = $eklaimResponse['metadata']['code'] ?? null;

        if ((string) $responseCode !== '200') {
            return new JsonResponse($eklaimResponse, 422);
        }

        $kunjungan = DB::table('rs17')
            ->where('rs1', $noreg)
            ->first();

        if (!$kunjungan) {
            return new JsonResponse(['message' => 'Data kunjungan tidak ditemukan.'], 404);
        }

        $pasien = DB::table('rs15')
            ->where('rs1', $kunjungan->rs2)
            ->first();

        DB::table('klaim_trans_rajal')->updateOrInsert(
            ['noreg' => $noreg, 'delete_status' => ''],
            [
                'nomor_kartu' => $validated['nomor_kartu'],
                'nomor_sep' => $nomorSep,
                'kelas_rawat' => 3,
                'birth_weight' => $pasien->berat_lahir ?? '',
                'discharge_status' => 1,
                'prosedur_non_bedah' => 0,
                'prosedur_bedah' => 0,
                'konsultasi' => 0,
                'tenaga_ahli' => 0,
                'keperawatan' => 0,
                'penunjang' => 0,
                'radiologi' => 0,
                'pelayanan_darah' => 0,
                'rehabilitasi' => 0,
                'kamar' => 0,
                'rawat_intensif' => 0,
                'obat' => 0,
                'alkes' => 0,
                'laboratorium' => 0,
                'bmhp' => 0,
                'sewa_alat' => 0,
                'tarif_poli_eks' => 0,
                'kode_tarif' => 'CP',
                'payor_id' => 3,
                'payor_cd' => 'JKN',
                'cob_cd' => '',
                'status_klaim' => 'Tersimpan',
                'tgl_update' => now(),
            ]
        );

        return new JsonResponse([
            'success' => true,
            'message' => 'Klaim berhasil ditambahkan.',
            'metadata' => $eklaimResponse['metadata'] ?? [],
            'data' => $eklaimResponse['data'] ?? null,
            'klaim' => DB::table('klaim_trans_rajal')
                ->where('noreg', $noreg)
                ->where('delete_status', '')
                ->first(),
        ]);
    }

    public function groupingIdrg(GroupIdrgRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $noreg = trim($validated['noreg']);
        $klaim = DB::table('klaim_trans_rajal')
            ->where('noreg', $noreg)
            ->where('delete_status', '')
            ->first();

        $field = static fn (string $key, mixed $fallback = null) =>
            $request->exists($key) ? $request->input($key) : $fallback;

        $nomorSep = trim((string) $field('nomor_sep', $klaim?->nomor_sep ?? ''));
        if ($nomorSep === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Nomor SEP klaim tidak ditemukan.',
            ], 422);
        }

        if (!$klaim) {
            $newClaimResponse = Eklaim::curl_func([
                'metadata' => ['method' => 'new_claim'],
                'data' => [
                    'nomor_kartu' => $field('nomor_kartu', ''),
                    'nomor_sep' => $nomorSep,
                    'nomor_rm' => $field('nomor_rm', ''),
                    'nama_pasien' => $field('nama_pasien', ''),
                    'tgl_lahir' => $field('tgl_lahir', ''),
                    'gender' => $field('gender', ''),
                ],
            ]);

            if ((string) ($newClaimResponse['metadata']['code'] ?? '') !== '200') {
                return new JsonResponse($newClaimResponse, 422);
            }

            DB::table('klaim_trans_rajal')->insert([
                'noreg' => $noreg,
                'nomor_kartu' => $field('nomor_kartu', ''),
                'nomor_sep' => $nomorSep,
                'norm' => $field('nomor_rm', ''),
                'kelas_rawat' => $field('kelas_rawat', 3),
                'discharge_status' => $field('discharge_status', 1),
                'kode_tarif' => 'CP',
                'payor_id' => $field('payor_id', 3),
                'payor_cd' => $field('payor', 'JKN'),
                'cob_cd' => $field('cob_cd', ''),
                'status_klaim' => 'Tersimpan',
                'delete_status' => '',
                'tgl_insert' => now(),
                'tgl_update' => now(),
            ]);

            $klaim = DB::table('klaim_trans_rajal')
                ->where('noreg', $noreg)
                ->where('delete_status', '')
                ->first();
        }

        $kunjungan = DB::table('rs17')
            ->select('rs2 as norm', 'rs3 as tgl_masuk', 'rs26 as tgl_pulang', 'rs9 as kd_dokter')
            ->where('rs1', $noreg)
            ->first();
        $pasien = $kunjungan
            ? DB::table('rs15')->where('rs1', $kunjungan->norm)->first()
            : null;

        $diagnosa = DB::table('diagnosa_klaim')
            ->where('noreg', $noreg)
            ->orderBy('id')
            ->pluck('icd')
            ->filter(fn ($icd) => trim((string) $icd) !== '')
            ->implode('#');

        if ($diagnosa === '') {
            return new JsonResponse([
                'success' => false,
                'stage' => 'idrg_diagnosa_set',
                'message' => 'Diagnosis klaim belum tersedia untuk noreg tersebut.',
            ], 422);
        }

        $prosedur = DB::table('prosedur_klaim')
            ->where('noreg', $noreg)
            ->orderBy('id')
            ->get(['kd_prosedur', 'jumlah'])
            ->map(function ($item) {
                $kode = trim((string) $item->kd_prosedur);
                $jumlah = trim((string) $item->jumlah);

                if ($kode === '') {
                    return null;
                }

                return $jumlah !== '' && $jumlah !== '1'
                    ? $kode.'+'.$jumlah
                    : $kode;
            })
            ->filter()
            ->implode('#');
        $prosedur = $prosedur !== '' ? $prosedur : '0';

        $klaimForm = [
            'nomor_kartu' => $field('nomor_kartu', $klaim->nomor_kartu ?? ''),
            'nomor_sep' => $nomorSep,
            'norm' => $field('nomor_rm', $kunjungan->norm ?? $klaim->norm ?? ''),
            'tgl_masuk' => $field('tgl_masuk', $kunjungan->tgl_masuk ?? null),
            'tgl_pulang' => $field('tgl_pulang', $kunjungan->tgl_pulang ?? null),
            'jenis_rawat' => $field('jenis_rawat', $klaim->jenis_rawat ?: 2),
            'kelas_rawat' => $field('kelas_rawat', $klaim->kelas_rawat ?: 3),
            'adl_sub_acute' => $field('adl_sub_acute', $klaim->adl_sub_acute ?? ''),
            'adl_chronic' => $field('adl_chronic', $klaim->adl_chronic ?? ''),
            'icu_indikator' => $field('icu_indikator', $klaim->icu_indikator ?? ''),
            'icu_los' => $field('icu_los', $klaim->icu_los ?? ''),
            'ventilator_hour' => $field('ventilator_hour', $klaim->ventilator_hour ?? ''),
            'upgrade_class_ind' => $field('upgrade_class_ind', $klaim->upgrade_class_ind ?? ''),
            'upgrade_class_class' => $field('upgrade_class_class', $klaim->upgrade_class_class ?? ''),
            'upgrade_class_los' => $field('upgrade_class_los', $klaim->upgrade_class_los ?? ''),
            'add_payment_pct' => $field('add_payment_pct', $klaim->add_payment_pct ?? ''),
            'birth_weight' => $field('birth_weight', $klaim->birth_weight ?? $pasien->berat_lahir ?? ''),
            'discharge_status' => $field('discharge_status', $klaim->discharge_status ?: 1),
            'cara_masuk' => $field('cara_masuk', $klaim->cara_masuk ?? ''),
            'sistole' => $field('sistole', $klaim->sistole ?? ''),
            'diastole' => $field('diastole', $klaim->diastole ?? ''),
        ];

        $tarifMap = [
            'prosedur_non_bedah', 'prosedur_bedah', 'konsultasi', 'tenaga_ahli',
            'keperawatan', 'penunjang', 'radiologi', 'laboratorium', 'pelayanan_darah',
            'rehabilitasi', 'kamar', 'rawat_intensif', 'obat', 'obat_kronis',
            'obat_kemoterapi', 'alkes', 'bmhp', 'sewa_alat', 'tarif_poli_eks',
        ];
        $tarif = [];
        foreach ($tarifMap as $column) {
            $tarif[$column] = $field($column, $klaim->{$column} ?? 0);
        }

        // Tabel klaim_trans_rajal tidak memiliki kolom obat_kronis/obat_kemoterapi.
        // Keduanya tetap dikirim ke E-Klaim, tetapi tidak ikut dalam update lokal.
        $tarifLokal = collect($tarif)->only([
            'prosedur_non_bedah', 'prosedur_bedah', 'konsultasi', 'tenaga_ahli',
            'keperawatan', 'penunjang', 'radiologi', 'laboratorium',
            'pelayanan_darah', 'rehabilitasi', 'kamar', 'rawat_intensif',
            'obat', 'alkes', 'bmhp', 'sewa_alat', 'tarif_poli_eks',
        ])->all();

        // Nilai UI berbeda dengan kode tarif yang diwajibkan E-Klaim.
        $kodeTarif = trim((string) $field('kode_tarif', $klaim->kode_tarif ?? 'CP'));
        $kodeTarif = match ($kodeTarif) {
            'kelas_c_pemerintah', 'kelas_c', 'KELAS_C_PEMERINTAH' => 'CP',
            default => $kodeTarif,
        };

        $coderNik = trim((string) $field('coder_nik', ''));
        if ($coderNik === '') {
            $coderNik = trim((string) ($klaim->coder_nik ?? ''));
        }
        if ($coderNik === '') {
            $coderNik = trim((string) (DB::table('klaim_trans_rajal')
                ->whereNotNull('coder_nik')
                ->where('coder_nik', '<>', '')
                ->orderByDesc('tgl_update')
                ->value('coder_nik') ?? ''));
        }
        if ($coderNik === '') {
            $coderNik = trim((string) config('services.eklaim.coder_nik', env('EKLAIM_CODER_NIK', '')));
        }
        if ($coderNik === '') {
            $coderNik = trim((string) (auth()->user()?->pegawai?->nik ?? ''));
        }
        if ($coderNik === '') {
            return new JsonResponse([
                'success' => false,
                'stage' => 'set_claim_data',
                'message' => 'NIK coder belum tersedia pada data user login.',
            ], 422);
        }

        $setClaimDataPayload = [
            'metadata' => [
                'method' => 'set_claim_data',
                'nomor_sep' => $nomorSep,
            ],
            'data' => [
                'nomor_sep' => $nomorSep,
                'nomor_kartu' => $klaimForm['nomor_kartu'],
                'tgl_masuk' => $klaimForm['tgl_masuk'] ?? now()->format('Y-m-d H:i:s'),
                'tgl_pulang' => $klaimForm['tgl_pulang'] ?? now()->format('Y-m-d H:i:s'),
                'cara_masuk' => $klaimForm['cara_masuk'],
                'jenis_rawat' => $klaimForm['jenis_rawat'],
                'kelas_rawat' => $klaimForm['kelas_rawat'],
                'adl_sub_acute' => $klaimForm['adl_sub_acute'],
                'adl_chronic' => $klaimForm['adl_chronic'],
                'icu_indikator' => $klaimForm['icu_indikator'],
                'icu_los' => $klaimForm['icu_los'],
                'ventilator_hour' => $klaimForm['ventilator_hour'],
                'upgrade_class_ind' => $klaimForm['upgrade_class_ind'],
                'upgrade_class_class' => $klaimForm['upgrade_class_class'],
                'upgrade_class_los' => $klaimForm['upgrade_class_los'],
                'add_payment_pct' => $klaimForm['add_payment_pct'],
                'birth_weight' => $klaimForm['birth_weight'],
                'discharge_status' => $klaimForm['discharge_status'],
                'tarif_rs' => [
                    'prosedur_non_bedah' => $tarif['prosedur_non_bedah'],
                    'prosedur_bedah' => $tarif['prosedur_bedah'],
                    'konsultasi' => $tarif['konsultasi'],
                    'tenaga_ahli' => $tarif['tenaga_ahli'],
                    'keperawatan' => $tarif['keperawatan'],
                    'penunjang' => $tarif['penunjang'],
                    'radiologi' => $tarif['radiologi'],
                    'laboratorium' => $tarif['laboratorium'],
                    'pelayanan_darah' => $tarif['pelayanan_darah'],
                    'rehabilitasi' => $tarif['rehabilitasi'],
                    'kamar' => $tarif['kamar'],
                    'rawat_intensif' => $tarif['rawat_intensif'],
                    'obat' => $tarif['obat'],
                    'obat_kronis' => $tarif['obat_kronis'],
                    'obat_kemoterapi' => $tarif['obat_kemoterapi'],
                    'alkes' => $tarif['alkes'],
                    'bmhp' => $tarif['bmhp'],
                    'sewa_alat' => $tarif['sewa_alat'],
                ],
                'tarif_poli_eks' => $tarif['tarif_poli_eks'],
                'nama_dokter' => $field('nama_dokter', $klaim->nama_dokter ?? ''),
                'kode_tarif' => $kodeTarif,
                'payor_id' => $field('payor_id', $klaim->payor_id ?? 3),
                'payor_cd' => $field('payor', $klaim->payor_cd ?? 'JKN'),
                'cob_cd' => $field('cob_cd', $klaim->cob_cd ?? ''),
                'coder_nik' => $coderNik,
            ],
        ];
        Log::debug('E-Klaim set_claim_data payload', [
            'noreg' => $noreg,
            'payload' => $setClaimDataPayload,
        ]);
        $setClaimDataResponse = Eklaim::curl_func($setClaimDataPayload);
        if ((string) ($setClaimDataResponse['metadata']['code'] ?? '') !== '200') {
            return new JsonResponse($setClaimDataResponse, 422);
        }

        DB::table('klaim_trans_rajal')
            ->where('noreg', $noreg)
            ->where('delete_status', '')
            ->update(array_merge($klaimForm, $tarifLokal, [
                'kode_tarif' => $kodeTarif,
                'payor_id' => $field('payor_id', $klaim->payor_id ?? 3),
                'payor_cd' => $field('payor', $klaim->payor_cd ?? 'JKN'),
                'cob_cd' => $field('cob_cd', $klaim->cob_cd ?? ''),
                'kd_dokter' => $field('kd_dokter', $klaim->kd_dokter ?? ($kunjungan->kd_dokter ?? '')),
                'nama_dokter' => $field('nama_dokter', $klaim->nama_dokter ?? ''),
                'coder_nik' => $coderNik,
                'tgl_update' => now(),
            ]));

        $diagnosaSetPayload = [
            'metadata' => [
                'method' => 'idrg_diagnosa_set',
                'nomor_sep' => $nomorSep,
            ],
            'data' => ['diagnosa' => $diagnosa],
        ];
        Log::debug('E-Klaim idrg_diagnosa_set payload', [
            'noreg' => $noreg,
            'payload' => $diagnosaSetPayload,
        ]);
        $diagnosaSetResponse = Eklaim::curl_func($diagnosaSetPayload);
        if ((string) ($diagnosaSetResponse['metadata']['code'] ?? '') !== '200') {
            return new JsonResponse($diagnosaSetResponse, 422);
        }

        $procedureSetPayload = [
            'metadata' => [
                'method' => 'idrg_procedure_set',
                'nomor_sep' => $nomorSep,
            ],
            'data' => ['procedure' => $prosedur],
        ];
        Log::debug('E-Klaim idrg_procedure_set payload', [
            'noreg' => $noreg,
            'payload' => $procedureSetPayload,
        ]);
        $procedureSetResponse = Eklaim::curl_func($procedureSetPayload);
        if ((string) ($procedureSetResponse['metadata']['code'] ?? '') !== '200') {
            return new JsonResponse($procedureSetResponse, 422);
        }

        $stage = (string) ($request->input('stage', $request->input('stages', '1')));
        $groupingPayload = [
            'metadata' => [
                'method' => 'grouper',
                'stage' => in_array($stage, ['1', '2'], true) ? $stage : '1',
                'grouper' => 'idrg',
            ],
            'data' => ['nomor_sep' => $nomorSep],
        ];
        if ($groupingPayload['metadata']['stage'] === '2') {
            $topupCodes = collect([
                $request->input('procedure'),
                $request->input('prosthesis'),
                $request->input('investigation'),
                $request->input('drug'),
            ])->filter(fn ($value) => trim((string) $value) !== '')->implode('#');
            $groupingPayload['data']['topup_codes'] = $topupCodes !== '' ? $topupCodes : '#';
        }

        Log::debug('E-Klaim grouper payload', [
            'noreg' => $noreg,
            'payload' => $groupingPayload,
        ]);
        $groupingResponse = Eklaim::curl_func($groupingPayload);
        if ((string) ($groupingResponse['metadata']['code'] ?? '') !== '200') {
            return new JsonResponse($groupingResponse, 422);
        }

        $grouping = $groupingResponse['response_idrg']
            ?? $groupingResponse['response']['response_idrg']
            ?? $groupingResponse['data']['response_idrg']
            ?? $groupingResponse['data']
            ?? [];
        if (!is_array($grouping) || !$this->adaDataEklaim($grouping)) {
            return new JsonResponse($groupingResponse, 422);
        }
        $hasGrouping = is_array($grouping) && collect([
            'mdc_number', 'mdc_description', 'drg_code', 'drg_description',
            'cost_weight', 'total_cost_weight', 'nbr', 'status_cd',
        ])->contains(fn ($key) => isset($grouping[$key]) && (string) $grouping[$key] !== '');

        if (!$hasGrouping) {
            return new JsonResponse($groupingResponse, 422);
        }

        $topup = [
            'procedure' => ['code' => null, 'description' => null, 'type' => null, 'cost_weight' => null],
            'prosthesis' => ['code' => null, 'description' => null, 'type' => null, 'cost_weight' => null],
            'investigation' => ['code' => null, 'description' => null, 'type' => null, 'cost_weight' => null],
            'diagnostic' => ['code' => null, 'description' => null, 'type' => null, 'cost_weight' => null],
            'drug' => ['code' => null, 'description' => null, 'type' => null, 'cost_weight' => null],
        ];
        $selectedTopupCodes = [
            'procedure' => trim((string) $request->input('procedure', '')),
            'prosthesis' => trim((string) $request->input('prosthesis', '')),
            'investigation' => trim((string) $request->input('investigation', '')),
            'diagnostic' => trim((string) $request->input('diagnostic', '')),
            'drug' => trim((string) $request->input('drug', '')),
        ];
        foreach (($grouping['topup_options'] ?? []) as $option) {
            $type = $option['type'] ?? null;
            $code = trim((string) ($option['code'] ?? ''));
            if (isset($topup[$type]) && $code !== '' && $selectedTopupCodes[$type] === $code) {
                $topup[$type] = [
                    'code' => $code,
                    'description' => $option['description'] ?? null,
                    'type' => $type,
                    'cost_weight' => $option['cost_weight'] ?? null,
                ];
            }
        }
        $optCmg = isset($grouping['topup_options']) && is_array($grouping['topup_options'])
            ? json_encode($grouping['topup_options'], JSON_THROW_ON_ERROR)
            : null;

        DB::transaction(function () use ($noreg, $klaimForm, $nomorSep, $grouping, $topup, $optCmg, $request) {
            DB::table('idrg_klaim')->updateOrInsert(
                ['noreg' => $noreg],
                [
                    'norm' => $klaimForm['norm'],
                    'nosep' => $nomorSep,
                    'jenis_rawat' => $grouping['jenis_rawat'] ?? '2',
                    'kelas_rawat' => $grouping['kelas_rawat'] ?? '3',
                    'mdc_number' => $grouping['mdc_number'] ?? null,
                    'mdc_description' => $grouping['mdc_description'] ?? null,
                    'drg_code' => $grouping['drg_code'] ?? null,
                    'drg_description' => $grouping['drg_description'] ?? null,
                    'script_version' => $grouping['script_version'] ?? null,
                    'logic_version' => $grouping['logic_version'] ?? null,
                    'cost_weight' => $grouping['cost_weight'] ?? null,
                    'sub_acute_weight' => $grouping['sub_acute_weight'] ?? null,
                    'chronic_weight' => $grouping['chronic_weight'] ?? null,
                    'total_cost_weight' => $grouping['total_cost_weight'] ?? null,
                    'total_tarif' => $grouping['total_tarif'] ?? null,
                    'nbr' => $grouping['nbr'] ?? null,
                    'status_cd' => $grouping['status_cd'] ?? 'grouping',
                    'opt_cmg' => $optCmg,
                    'drug_opt' => $request->input('drug'),
                    'procedure_opt' => $request->input('procedure'),
                    'prosthesis_opt' => $request->input('prosthesis'),
                    'investigation_opt' => $request->input('investigation'),
                    'diagnostic_opt' => $request->input('diagnostic'),
                    'topup_procedure_code' => $topup['procedure']['code'],
                    'topup_procedure_desc' => $topup['procedure']['description'],
                    'topup_procedure_type' => $topup['procedure']['type'],
                    'topup_procedure_cost_weight' => $topup['procedure']['cost_weight'],
                    'topup_prosthesis_code' => $topup['prosthesis']['code'],
                    'topup_prosthesis_desc' => $topup['prosthesis']['description'],
                    'topup_prosthesis_type' => $topup['prosthesis']['type'],
                    'topup_prosthesis_cost_weight' => $topup['prosthesis']['cost_weight'],
                    'topup_investigation_code' => $topup['investigation']['code'],
                    'topup_investigation_desc' => $topup['investigation']['description'],
                    'topup_investigation_type' => $topup['investigation']['type'],
                    'topup_investigation_cost_weight' => $topup['investigation']['cost_weight'],
                    'topup_diagnostic_code' => $topup['diagnostic']['code'],
                    'topup_diagnostic_desc' => $topup['diagnostic']['description'],
                    'topup_diagnostic_type' => $topup['diagnostic']['type'],
                    'topup_diagnostic_cost_weight' => $topup['diagnostic']['cost_weight'],
                    'topup_drug_code' => $topup['drug']['code'],
                    'topup_drug_desc' => $topup['drug']['description'],
                    'topup_drug_type' => $topup['drug']['type'],
                    'topup_drug_cost_weight' => $topup['drug']['cost_weight'],
                    'flaging' => 'GrouperIdrg',
                    'userentry' => auth()->user()->pegawai_id ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        });

        $idrgKlaim = DB::table('idrg_klaim')->where('noreg', $noreg)->first();
        $specialDrugOptions = $this->specialDrugOptions(
            $grouping['special_cmg_option_code']
                ?? $grouping['opt_cmg']
                ?? $idrgKlaim?->opt_cmg
                ?? []
        );

        return new JsonResponse([
            'success' => true,
            'has_grouping' => true,
            'message' => 'Grouping iDRG berhasil.',
            'diagnosa' => $diagnosa,
            'procedure' => $prosedur,
            'grouping' => $grouping,
            'special_drug_options' => $specialDrugOptions,
            'idrg_klaim' => $idrgKlaim,
        ]);

    }

    private function specialDrugOptions(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($value)) return [];

        $flatten = function (array $items) use (&$flatten): array {
            return collect($items)->flatMap(function ($item) use (&$flatten) {
                if (!is_array($item)) return [];
                if (isset($item['code'], $item['description']) || isset($item['code'], $item['label'])) {
                    return [$item];
                }
                return $flatten($item);
            })->all();
        };

        $options = collect($flatten($value))
        ->filter(function ($item) {
            $type = strtolower(trim((string) ($item['type'] ?? '')));
            return in_array($type, ['special drug', 'drug'], true);
        })->map(function ($item) {
            $code = trim((string) ($item['code'] ?? $item['kode'] ?? $item['value'] ?? ''));
            $description = trim((string) ($item['description'] ?? $item['nama'] ?? $item['label'] ?? $code));
            return ['label' => $description, 'value' => $code];
        })->filter(fn ($item) => $item['value'] !== '')->unique('value')->values();

        return $options->all();
    }

        /*
            ->select('noreg', 'nomor_sep', 'nomor_kartu')
            ->where('noreg', $noreg)
            ->where('delete_status', '')
            ->first();

        $nomorSep = trim((string) ($validated['nomor_sep'] ?? $klaim->nomor_sep ?? ''));
        if (!$klaim || $nomorSep === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Nomor SEP klaim tidak ditemukan.',
            ], 422);
        }

        $diagnosa = DB::table('diagnosa_klaim')
            ->where('noreg', $noreg)
            ->orderBy('id')
            ->pluck('icd')
            ->filter(fn ($icd) => trim((string) $icd) !== '')
            ->implode('#');

        $prosedur = DB::table('prosedur_klaim')
            ->where('noreg', $noreg)
            ->get(['kd_prosedur', 'jumlah'])
            ->map(function ($item) {
                $kode = trim((string) $item->kd_prosedur);
                $jumlah = trim((string) $item->jumlah);
                if ($kode === '') return null;
                return $jumlah !== '' && $jumlah !== '1' ? $kode.'+'.$jumlah : $kode;
            })
            ->filter()
            ->implode('#');
        $prosedur = $prosedur !== '' ? $prosedur : '0';

        $kunjungan = DB::table('rs17')
            ->select('rs3 as tgl_masuk', 'rs26 as tgl_pulang', 'rs9 as kd_dokter')
            ->where('rs1', $noreg)
            ->first();
        $pasien = $kunjungan
            ? DB::table('rs15')->where('rs1', $klaim->nomor_kartu)->first()
            : null;

        $setClaimDataResponse = Eklaim::curl_func([
            'metadata' => [
                'method' => 'set_claim_data',
                'nomor_sep' => $nomorSep,
            ],
            'data' => [
                'nomor_sep' => $nomorSep,
                'nomor_kartu' => $klaim->nomor_kartu,
                'tgl_masuk' => $kunjungan->tgl_masuk ?? now()->format('Y-m-d H:i:s'),
                'tgl_pulang' => $kunjungan->tgl_pulang ?? now()->format('Y-m-d H:i:s'),
                'cara_masuk' => '',
                'jenis_rawat' => 2,
                'kelas_rawat' => 3,
                'adl_sub_acute' => '',
                'adl_chronic' => '',
                'icu_indikator' => '',
                'icu_los' => '',
                'ventilator_hour' => '',
                'upgrade_class_ind' => '',
                'upgrade_class_class' => '',
                'upgrade_class_los' => '',
                'add_payment_pct' => '',
                'birth_weight' => $pasien->berat_lahir ?? '',
                'discharge_status' => 1,
                'diagnosa' => $diagnosa,
                'procedure' => $prosedur,
                'tarif_rs' => [
                    'prosedur_non_bedah' => 0,
                    'prosedur_bedah' => 0,
                    'konsultasi' => 0,
                    'tenaga_ahli' => 0,
                    'keperawatan' => 0,
                    'penunjang' => 0,
                    'radiologi' => 0,
                    'laboratorium' => 0,
                    'pelayanan_darah' => 0,
                    'rehabilitasi' => 0,
                    'kamar' => 0,
                    'rawat_intensif' => 0,
                    'obat' => 0,
                    'obat_kronis' => 0,
                    'obat_kemoterapi' => 0,
                    'alkes' => 0,
                    'bmhp' => 0,
                    'sewa_alat' => 0,
                ],
                'tarif_poli_eks' => 0,
                'nama_dokter' => '',
                'kode_tarif' => 'CP',
                'payor_id' => 3,
                'payor_cd' => 'JKN',
                'cob_cd' => '',
                'coder_nik' => '',
            ],
        ]);
        if ((string) ($setClaimDataResponse['metadata']['code'] ?? '') !== '200') {
            return new JsonResponse($setClaimDataResponse, 422);
        }

        $diagnosaSetResponse = Eklaim::curl_func([
            'metadata' => [
                'method' => 'idrg_diagnosa_set',
                'nomor_sep' => $nomorSep,
            ],
            'data' => ['diagnosa' => $diagnosa],
        ]);
        if ((string) ($diagnosaSetResponse['metadata']['code'] ?? '') !== '200') {
            return new JsonResponse($diagnosaSetResponse, 422);
        }

        $procedureSetResponse = Eklaim::curl_func([
            'metadata' => [
                'method' => 'idrg_procedure_set',
                'nomor_sep' => $nomorSep,
            ],
            'data' => ['procedure' => $prosedur],
        ]);
        if ((string) ($procedureSetResponse['metadata']['code'] ?? '') !== '200') {
            return new JsonResponse($procedureSetResponse, 422);
        }

        $stage = (string) ($request->input('stage', $request->input('stages', '1')));
        $groupingPayload = [
            'metadata' => [
                'method' => 'grouper',
                'stage' => in_array($stage, ['1', '2'], true) ? $stage : '1',
                'grouper' => 'idrg',
            ],
            'data' => ['nomor_sep' => $nomorSep],
        ];
        if ($groupingPayload['metadata']['stage'] === '2') {
            $topupCodes = collect([
                $request->input('procedure'),
                $request->input('prosthesis'),
                $request->input('investigation'),
                $request->input('drug'),
            ])->filter(fn ($value) => trim((string) $value) !== '')->implode('#');
            $groupingPayload['data']['topup_codes'] = $topupCodes !== '' ? $topupCodes : '#';
        }

        $groupingResponse = Eklaim::curl_func($groupingPayload);
        if ((string) ($groupingResponse['metadata']['code'] ?? '') !== '200') {
            return new JsonResponse($groupingResponse, 422);
        }

        $grouping = $groupingResponse['response_idrg'] ?? $groupingResponse['data']['response_idrg'] ?? $groupingResponse['data'] ?? [];
        if (!is_array($grouping) || !$this->adaDataEklaim($grouping)) {
            return new JsonResponse($groupingResponse, 422);
        }
        $hasGrouping = is_array($grouping) && collect([
            'mdc_number', 'mdc_description', 'drg_code', 'drg_description',
            'cost_weight', 'total_cost_weight', 'nbr', 'status_cd',
        ])->contains(fn ($key) => isset($grouping[$key]) && (string) $grouping[$key] !== '');

        if (!$hasGrouping) {
            return new JsonResponse($groupingResponse, 422);
        }

        if ($hasGrouping) {
            DB::table('idrg_klaim')->updateOrInsert(
                ['noreg' => $noreg],
                [
                    'norm' => $klaim->nomor_kartu,
                    'nosep' => $nomorSep,
                    'jenis_rawat' => $grouping['jenis_rawat'] ?? '2',
                    'kelas_rawat' => $grouping['kelas_rawat'] ?? '3',
                    'mdc_number' => $grouping['mdc_number'] ?? null,
                    'mdc_description' => $grouping['mdc_description'] ?? null,
                    'drg_code' => $grouping['drg_code'] ?? null,
                    'drg_description' => $grouping['drg_description'] ?? null,
                    'script_version' => $grouping['script_version'] ?? null,
                    'logic_version' => $grouping['logic_version'] ?? null,
                    'cost_weight' => $grouping['cost_weight'] ?? null,
                    'sub_acute_weight' => $grouping['sub_acute_weight'] ?? null,
                    'chronic_weight' => $grouping['chronic_weight'] ?? null,
                    'total_cost_weight' => $grouping['total_cost_weight'] ?? null,
                    'nbr' => $grouping['nbr'] ?? null,
                    'status_cd' => $grouping['status_cd'] ?? 'grouping',
                    'flaging' => '1',
                    'userentry' => auth()->user()->pegawai_id ?? null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return new JsonResponse([
            'success' => true,
            'has_grouping' => $hasGrouping,
            'message' => 'Data diagnosis, procedure, dan grouping IDRG berhasil diambil dari E-Klaim.',
            'diagnosa' => $diagnosaSetResponse['data'] ?? null,
            'procedure' => $procedureSetResponse['data'] ?? null,
            'claim_data' => $groupingResponse['data'] ?? null,
            'grouping' => $hasGrouping ? $grouping : null,
            'idrg_klaim' => $hasGrouping ? DB::table('idrg_klaim')->where('noreg', $noreg)->first() : null,
        ]);
    }

        */
    private function adaDataEklaim(mixed $data): bool
    {
        if ($data === null || $data === '') {
            return false;
        }

        if (is_array($data)) {
            foreach ($data as $value) {
                if ($this->adaDataEklaim($value)) {
                    return true;
                }
            }

            return false;
        }

        return trim((string) $data) !== '';
    }

    public function kunjunganKlaim(Request $request): JsonResponse
    {
        $request->validate([
            'noreg' => ['required', 'string'],
        ]);

        $noreg = $request->input('noreg');

        $layanan = strtolower((string) $request->input('layanan', ''));
        $isRajal = $layanan === 'rajal'
            || ($layanan === '' && DB::table('rs17')->where('rs1', $noreg)->exists());

        if ($isRajal) {
            return $this->kunjunganKlaimRajal($noreg);
        }

        $kunjungan = DB::table('klaim_trans_ranap')
            ->join('rs23', 'klaim_trans_ranap.noreg', '=', 'rs23.rs1')
            ->join('rs15', 'rs23.rs2', '=', 'rs15.rs1')
            ->join('rs21', 'rs23.rs10', '=', 'rs21.rs1')
            ->select([
                'rs15.rs1 as norm',
                'rs15.rs2 as nama',
                'rs15.rs16 as tgllahir',
                'rs15.rs17 as kelamin',
                'klaim_trans_ranap.nomor_kartu as noka',
                'klaim_trans_ranap.tgl_masuk as tglmasuk',
                'klaim_trans_ranap.tgl_pulang as tglkeluar',
                'klaim_trans_ranap.nomor_sep as nosep',
                'rs23.rs1 as noreg',
                'rs21.rs2 as dokter',
                'rs23.rs10 as kd_dokter',
                'klaim_trans_ranap.konsulke',
                'klaim_trans_ranap.payor_id',
                'klaim_trans_ranap.cob_cd',
                'klaim_trans_ranap.discharge_status',
                'klaim_trans_ranap.kelas_rawat',
                'klaim_trans_ranap.tarif_poli_eks',
                'klaim_trans_ranap.adl_sub_acute',
                'klaim_trans_ranap.adl_chronic',
                'klaim_trans_ranap.icu_indikator',
                'klaim_trans_ranap.icu_los',
                'klaim_trans_ranap.ventilator_hour',
                'klaim_trans_ranap.upgrade_class_ind',
                'klaim_trans_ranap.upgrade_class_class',
                'klaim_trans_ranap.upgrade_class_los',
                'klaim_trans_ranap.add_payment_pct',
                'klaim_trans_ranap.birth_weight',
                'klaim_trans_ranap.jenis_rawat',
            ])
            ->where('rs23.rs1', $noreg)
            ->where('klaim_trans_ranap.delete_status', '')
            ->first();

        $sudahPernahKlaim = $kunjungan !== null ? 1 : 0;

        if (!$sudahPernahKlaim) {
            $kunjungan = DB::table('rs23')
                ->join('rs15', 'rs23.rs2', '=', 'rs15.rs1')
                ->join('rs21', 'rs23.rs10', '=', 'rs21.rs1')
                ->leftJoin('rs227', 'rs23.rs1', '=', 'rs227.rs1')
                ->select([
                    'rs15.rs1 as norm',
                    'rs15.rs2 as nama',
                    'rs15.rs16 as tgllahir',
                    'rs15.rs17 as kelamin',
                    'rs15.rs46 as noka',
                    'rs23.rs3 as tglmasuk',
                    'rs23.rs4 as tglkeluar',
                    'rs227.rs8 as nosep',
                    'rs23.rs1 as noreg',
                    'rs21.rs2 as dokter',
                    'rs23.rs10 as kd_dokter',
                    'rs23.rs38 as kelas_rawat',
                    DB::raw("CASE
                        WHEN rs23.rs23 = 'C001' THEN '1'
                        WHEN rs23.rs23 IN ('C002', 'C010') THEN '3'
                        WHEN rs23.rs23 = 'C003' THEN '4'
                        WHEN rs23.rs23 IN ('C005', 'C007', 'C008', 'C009', 'C012', 'C013', 'C014') THEN '2'
                        ELSE '5'
                    END as discharge_status"),
                    DB::raw("'1' as jenis_rawat"),
                ])
                ->where('rs23.rs1', $noreg)
                ->first();
        }

        if ($kunjungan) {
            $tandaVital = DB::table('rs253')
                ->leftJoin('rs253_sambung as sambung', 'rs253.id', '=', 'sambung.rs253_id')
                ->select('sambung.sistole', 'sambung.diastole')
                ->where('rs253.rs1', $noreg)
                ->where(function ($query) {
                    $query->whereNotNull('sambung.sistole')
                        ->orWhereNotNull('sambung.diastole');
                })
                ->orderByDesc('rs253.rs3')
                ->orderByDesc('rs253.id')
                ->first();
            $kunjungan->sistole = $tandaVital->sistole ?? null;
            $kunjungan->diastole = $tandaVital->diastole ?? null;
        }

        $covid19 = $sudahPernahKlaim
            ? DB::table('klaim_covid19')
                ->select([
                    'nomor_kartu_t',
                    'covid19_status_cd',
                    'episodes',
                    'covid19_cc_ind',
                    'pemulasaraan_jenazah',
                    'kantong_jenazah',
                    'peti_jenazah',
                    'plastik_erat',
                    'desinfektan_jenazah',
                    'mobil_jenazah',
                    'desinfektan_mobil_jenazah',
                    'akses_naat',
                    'covid19_co_insidense_ind',
                ])
                ->where('noreg', $noreg)
                ->first()
            : DB::table('tflag_covid')
                ->select('flagcovid as covid19_status_cd')
                ->where('noreg', $noreg)
                ->first();

        // Selalu ambil hasil grouping terbaru dari database untuk ditampilkan di front-end.
        $idrgKlaim = DB::table('idrg_klaim')
            ->where('noreg', trim($noreg))
            ->first();

        return new JsonResponse([
            'data' => $kunjungan,
            'covid19' => $covid19,
            'sudahpernahklaim' => $sudahPernahKlaim,
            'total_tarif' => 0,
            'idrg_klaim' => $idrgKlaim,
            'layanan' => 'ranap',
        ]);
    }

    private function kunjunganKlaimRajal(string $noreg): JsonResponse
    {
        $eklaimData = null;
        $klaimAwal = DB::table('klaim_trans_rajal')
            ->select('nomor_sep')
            ->where('noreg', $noreg)
            ->where('delete_status', '')
            ->first();

        // E-Klaim menjadi sumber utama bila memiliki data; query lokal tetap fallback.
        if ($klaimAwal && trim((string) $klaimAwal->nomor_sep) !== '') {
            $eklaimData = $this->sinkronkanDataKlaimEklaim($noreg, $klaimAwal->nomor_sep);
        }

        $kunjungan = DB::table('klaim_trans_rajal')
            ->join('rs17', 'klaim_trans_rajal.noreg', '=', 'rs17.rs1')
            ->join('rs15', 'rs17.rs2', '=', 'rs15.rs1')
            ->join('rs21', 'rs17.rs9', '=', 'rs21.rs1')
            ->select([
                'rs15.rs1 as norm',
                'rs15.rs2 as nama',
                'rs15.rs16 as tgllahir',
                'rs15.rs17 as kelamin',
                'klaim_trans_rajal.nomor_kartu as noka',
                'rs17.rs3 as tglmasuk',
                'rs17.rs3 as tglkeluar',
                'klaim_trans_rajal.nomor_sep as nosep',
                'rs17.rs1 as noreg',
                'rs21.rs2 as dokter',
                'rs17.rs9 as kd_dokter',
                'klaim_trans_rajal.konsulke',
                'klaim_trans_rajal.payor_id',
                'klaim_trans_rajal.cob_cd',
                'klaim_trans_rajal.kode_tarif',
                'klaim_trans_rajal.discharge_status',
                'klaim_trans_rajal.kelas_rawat',
                'rs17.rs8 as kdPoli',
                'rs17.rs14 as kdsistembayar',
                'klaim_trans_rajal.tarif_poli_eks',
                'klaim_trans_rajal.birth_weight',
                DB::raw("'2' as jenis_rawat"),
            ])
            ->where('rs17.rs1', $noreg)
            ->where('klaim_trans_rajal.delete_status', '')
            ->first();

        $sudahPernahKlaim = $kunjungan !== null ? 1 : 0;

        if (!$sudahPernahKlaim) {
            $kunjungan = DB::table('rs17')
                ->join('rs15', 'rs17.rs2', '=', 'rs15.rs1')
                ->join('rs21', 'rs17.rs9', '=', 'rs21.rs1')
                ->leftJoin('rs222', 'rs17.rs1', '=', 'rs222.rs1')
                ->select([
                    'rs15.rs1 as norm',
                    'rs15.rs2 as nama',
                    'rs15.rs16 as tgllahir',
                    'rs15.rs17 as kelamin',
                    'rs15.rs46 as noka',
                    'rs17.rs3 as tglmasuk',
                    DB::raw('DATE_ADD(rs17.rs3, INTERVAL 10 MINUTE) as tglkeluar'),
                    'rs222.rs8 as nosep',
                    'rs17.rs1 as noreg',
                    'rs21.rs2 as dokter',
                    'rs17.rs9 as kd_dokter',
                    'rs17.rs8 as kdPoli',
                    'rs17.rs14 as kdsistembayar',
                    DB::raw("'' as kelas_rawat"),
                    DB::raw("'1' as discharge_status"),
                    DB::raw("'2' as jenis_rawat"),
                ])
                ->where('rs17.rs1', $noreg)
                ->first();
        }

        $covid19 = $sudahPernahKlaim
            ? DB::table('klaim_covid19')
                ->select([
                    'nomor_kartu_t',
                    'covid19_status_cd',
                    'episodes',
                    'covid19_cc_ind',
                    'pemulasaraan_jenazah',
                    'kantong_jenazah',
                    'peti_jenazah',
                    'plastik_erat',
                    'desinfektan_jenazah',
                    'mobil_jenazah',
                    'desinfektan_mobil_jenazah',
                    'akses_naat',
                    'covid19_co_insidense_ind',
                ])
                ->where('noreg', $noreg)
                ->first()
            : DB::table('tflag_covid')
                ->select('flagcovid as covid19_status_cd')
                ->where('noreg', $noreg)
                ->first();

        if ($kunjungan && $kunjungan->kdPoli === 'PEN004') {
            $dokter = DB::table('rs201')
                ->leftJoin('rs21', 'rs21.rs1', '=', 'rs201.rs16')
                ->select('rs201.rs16 as kd_dokter', 'rs21.rs2 as dokter')
                ->where('rs201.rs1', $noreg)
                ->first();

            if ($dokter) {
                $kunjungan->kd_dokter = $dokter->kd_dokter;
                $kunjungan->dokter = $dokter->dokter;
            }
        }

        $tandaVital = null;
        $jumlahKantongDarah = 0;
        if ($kunjungan) {
            if ($kunjungan->kdPoli === 'POL014') {
                $tandaVital = DB::table('rs250')
                    ->select('sistole', 'diastole')
                    ->where('rs1', $noreg)
                    ->first();
                $jumlahKantongDarah = DB::table('rs231')
                    ->where('rs1', trim($noreg))
                    ->where('rs14', 'POL014')
                    ->count();
            } else {
                $tandaVital = DB::table('rs236')
                    ->select('sistole', 'diastole')
                    ->where('rs1', $noreg)
                    ->first();
                $jumlahKantongDarah = DB::table('rs231')
                    ->where('rs1', trim($noreg))
                    ->count();
            }

            $kunjungan->sistole = $tandaVital->sistole ?? null;
            $kunjungan->diastole = $tandaVital->diastole ?? null;
            $kunjungan->jumlah_kantong_darah = $jumlahKantongDarah;
        }

        // Jangan memakai respons grouping yang lama; ambil record terbaru dari DB.
        $idrgKlaim = DB::table('idrg_klaim')
            ->where('noreg', trim($noreg))
            ->first();

        return new JsonResponse([
            'data' => $kunjungan,
            'data_eklaim' => $eklaimData,
            'idrg_klaim' => DB::table('idrg_klaim')
                ->where('noreg', trim($noreg))
                ->first(),
            'total_tarif_eklaim' => $this->ambilTotalTarifEklaim($eklaimData),
            'covid19' => $covid19,
            'sudahpernahklaim' => $sudahPernahKlaim,
            'total_tarif' => 0,
            'flagidrg' => DB::table('idrg_klaim')->where('noreg', trim($noreg))->exists() ? 1 : 0,
            'idrg_klaim' => $idrgKlaim,
            'layanan' => 'rajal',
        ]);
    }

    private function sinkronkanGroupingIdrgEklaim(string $noreg, string $nomorSep): void
    {
        if (!DB::table('idrg_klaim')->where('noreg', $noreg)->exists()) return;

        try {
            $response = Eklaim::curl_func([
                'metadata' => [
                    'method' => 'grouper',
                    'stage' => '1',
                    'grouper' => 'idrg',
                ],
                'data' => ['nomor_sep' => trim($nomorSep)],
            ]);

            if ((string) ($response['metadata']['code'] ?? '') !== '200') return;

            $grouping = $response['response_idrg']
                ?? $response['response']['response_idrg']
                ?? $response['data']['response_idrg']
                ?? $response['data']
                ?? [];
            if (!is_array($grouping) || !$this->adaDataEklaim($grouping)) return;

            $optCmg = isset($grouping['topup_options']) && is_array($grouping['topup_options'])
                ? json_encode($grouping['topup_options'], JSON_THROW_ON_ERROR)
                : null;

            DB::table('idrg_klaim')->where('noreg', $noreg)->update([
                'mdc_number' => $grouping['mdc_number'] ?? null,
                'mdc_description' => $grouping['mdc_description'] ?? null,
                'drg_code' => $grouping['drg_code'] ?? null,
                'drg_description' => $grouping['drg_description'] ?? null,
                'script_version' => $grouping['script_version'] ?? null,
                'logic_version' => $grouping['logic_version'] ?? null,
                'cost_weight' => $grouping['cost_weight'] ?? null,
                'sub_acute_weight' => $grouping['sub_acute_weight'] ?? null,
                'chronic_weight' => $grouping['chronic_weight'] ?? null,
                'total_cost_weight' => $grouping['total_cost_weight'] ?? null,
                'total_tarif' => $grouping['total_tarif'] ?? null,
                'nbr' => $grouping['nbr'] ?? null,
                'status_cd' => $grouping['status_cd'] ?? null,
                'opt_cmg' => $optCmg,
                'drug_opt' => null,
                'procedure_opt' => null,
                'prosthesis_opt' => null,
                'investigation_opt' => null,
                'diagnostic_opt' => null,
                'topup_procedure_code' => null,
                'topup_procedure_desc' => null,
                'topup_procedure_type' => null,
                'topup_procedure_cost_weight' => null,
                'topup_prosthesis_code' => null,
                'topup_prosthesis_desc' => null,
                'topup_prosthesis_type' => null,
                'topup_prosthesis_cost_weight' => null,
                'topup_investigation_code' => null,
                'topup_investigation_desc' => null,
                'topup_investigation_type' => null,
                'topup_investigation_cost_weight' => null,
                'topup_diagnostic_code' => null,
                'topup_diagnostic_desc' => null,
                'topup_diagnostic_type' => null,
                'topup_diagnostic_cost_weight' => null,
                'topup_drug_code' => null,
                'topup_drug_desc' => null,
                'topup_drug_type' => null,
                'topup_drug_cost_weight' => null,
                'topup_drug_tarif' => null,
                'updated_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private function sinkronkanDataKlaimEklaim(string $noreg, string $nomorSep): ?array
    {
        try {
            $response = Eklaim::curl_func([
                'metadata' => ['method' => 'get_claim_data'],
                'data' => ['nomor_sep' => trim($nomorSep)],
            ]);

            if ((string) ($response['metadata']['code'] ?? '') !== '200') {
                return $response;
            }

            // Response E-Klaim pada beberapa versi dibungkus sebagai response.data.
            $data = $response['response']['data']
                ?? $response['data']
                ?? $response['response']
                ?? null;
            if (!is_array($data)) {
                return $response;
            }

            // Dukungan wrapper tambahan dari versi WS yang berbeda.
            foreach (['claim', 'klaim', 'data'] as $wrapper) {
                if (isset($data[$wrapper]) && is_array($data[$wrapper])) {
                    $data = $data[$wrapper];
                    break;
                }
            }

            $totalTarifEklaim = $this->ambilTotalTarifEklaim($data);
            if ($totalTarifEklaim !== null) {
                DB::table('idrg_klaim')
                    ->where('noreg', $noreg)
                    ->update([
                        'total_tarif' => $totalTarifEklaim,
                        'updated_at' => now(),
                    ]);
            }

            $columnMap = [
                'nomor_kartu' => ['nomor_kartu', 'no_peserta'],
                'nomor_sep' => ['nomor_sep'],
                'tgl_masuk' => ['tgl_masuk'],
                'tgl_pulang' => ['tgl_pulang'],
                'jenis_rawat' => ['jenis_rawat'],
                'kelas_rawat' => ['kelas_rawat'],
                'adl_sub_acute' => ['adl_sub_acute'],
                'adl_chronic' => ['adl_chronic'],
                'icu_indikator' => ['icu_indikator'],
                'icu_los' => ['icu_los'],
                'ventilator_hour' => ['ventilator_hour'],
                'upgrade_class_ind' => ['upgrade_class_ind'],
                'upgrade_class_class' => ['upgrade_class_class'],
                'upgrade_class_los' => ['upgrade_class_los'],
                'add_payment_pct' => ['add_payment_pct'],
                'birth_weight' => ['birth_weight'],
                'discharge_status' => ['discharge_status'],
                'cara_masuk' => ['cara_masuk'],
                'sistole' => ['sistole'],
                'diastole' => ['diastole'],
            ];

            $updates = [];
            foreach ($columnMap as $column => $keys) {
                foreach ($keys as $key) {
                    if (array_key_exists($key, $data) && $data[$key] !== null && trim((string) $data[$key]) !== '') {
                        $updates[$column] = $data[$key];
                        break;
                    }
                }
            }

            $kolomKlaimTersedia = [
                'nomor_kartu', 'nomor_sep', 'tgl_masuk', 'tgl_pulang', 'jenis_rawat',
                'kelas_rawat', 'adl_sub_acute', 'adl_chronic', 'icu_indikator',
                'icu_los', 'ventilator_hour', 'upgrade_class_ind', 'upgrade_class_class',
                'upgrade_class_los', 'add_payment_pct', 'birth_weight', 'discharge_status',
                'cara_masuk', 'sistole', 'diastole',
            ];
            $updatesLokal = array_intersect_key($updates, array_flip($kolomKlaimTersedia));

            if ($updatesLokal) {
                $updatesLokal['tgl_update'] = now();
                DB::table('klaim_trans_rajal')
                    ->where('noreg', $noreg)
                    ->where('delete_status', '')
                    ->update($updatesLokal);
            }

            return $this->mapDataKlaimEklaim($data);
        } catch (\Throwable $exception) {
            // E-Klaim down/empty must not prevent the local claim query from being returned.
            report($exception);

            return null;
        }

    }

    private function ambilTotalTarifEklaim(?array $data): ?string
    {
        if (!$data) {
            return null;
        }

        foreach (['total_tarif', 'total_klaim', 'total_claim', 'tarif'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                return (string) $data[$field];
            }
        }

        return null;
    }

    private function mapDataKlaimEklaim(array $data): array
    {
        $mapped = $data;
        $aliases = [
            'nomor_kartu' => 'noka',
            'nomor_sep' => 'nosep',
            'nomor_rm' => 'norm',
            'nama_pasien' => 'nama',
            'tgl_lahir' => 'tgllahir',
            'tgl_masuk' => 'tglmasuk',
            'tgl_pulang' => 'tglkeluar',
        ];

        foreach ($aliases as $source => $target) {
            if (array_key_exists($source, $data) && $data[$source] !== null && $data[$source] !== '') {
                $mapped[$target] = $data[$source];
            }
        }

        // E-Klaim mengembalikan tarif dalam tarif_rs, sedangkan form Quasar
        // membaca field tarif secara flat.
        if (isset($data['tarif_rs']) && is_array($data['tarif_rs'])) {
            foreach ($data['tarif_rs'] as $field => $value) {
                if ($value !== null && $value !== '') {
                    $mapped[$field] = $value;
                }
            }
        }

        return $mapped;
    }

    public function tarif(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noreg_utama' => ['required', 'string'],
            'noreg' => ['nullable'],
            'noreg.*' => ['string'],
            'layanan' => ['nullable', 'in:rajal,ranap'],
        ]);

        $noregUtama = trim($validated['noreg_utama']);
        $noregTambahan = $validated['noreg'] ?? [];
        if (is_string($noregTambahan)) {
            $noregTambahan = strtolower($noregTambahan) === 'null'
                ? []
                : array_filter(array_map('trim', explode(',', $noregTambahan)));
        }
        $noreg = array_values(array_unique(array_merge([$noregUtama], $noregTambahan)));

        $isRanap = ($validated['layanan'] ?? '') === 'ranap';
        if ($isRanap) $noreg = [$noregUtama];

        $kolomTarif = [
            'prosedur_non_bedah', 'prosedur_bedah', 'konsultasi', 'tenaga_ahli',
            'keperawatan', 'penunjang', 'radiologi', 'laboratorium',
            'pelayanan_darah', 'rehabilitasi', 'kamar', 'rawat_intensif',
            'obat', 'alkes', 'bmhp', 'sewa_alat',
        ];

        $klaim = DB::table($isRanap ? 'klaim_trans_ranap' : 'klaim_trans_rajal')
            ->select($kolomTarif)
            ->where('noreg', $noregUtama)
            ->where('delete_status', '')
            ->whereIn('status_klaim', ['Final', 'Terkirim'])
            ->first();

        if ($klaim) {
            $tarif = collect($kolomTarif)->mapWithKeys(
                fn (string $kolom) => [$kolom => (float) ($klaim->{$kolom} ?? 0)]
            )->all();

            return new JsonResponse(array_merge($this->denganTotalTarif($tarif), ['layanan' => $isRanap ? 'ranap' : 'rajal']));
        }

        $tarif = array_fill_keys($kolomTarif, 0.0);
        $kodeDikecualikan = [
            'OPERASI', 'FISIO', 'POL024', 'POL026', 'PEN005', 'POL031',
            'OPERASIIRD2', 'OPERASIIRD', 'OPERASI2', 'POL029', 'POL030',
        ];

        $mapping = DB::table('rs73')
            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
            ->leftJoin('mapping_klaim', 'mapping_klaim.kode', '=', 'rs73.rs4')
            ->selectRaw("COALESCE(NULLIF(mapping_klaim.jenis, ''), 'Keperawatan') as jenis")
            ->selectRaw('SUM((rs73.rs7 + rs73.rs13) * rs73.rs5) as subtotal')
            ->whereIn('rs73.rs1', $noreg)
            ->whereNotIn('rs73.rs22', $kodeDikecualikan)
            ->groupByRaw("COALESCE(NULLIF(mapping_klaim.jenis, ''), 'Keperawatan')")
            ->pluck('subtotal', 'jenis');

        $petaMapping = [
            'Prosedur Non Bedah' => 'prosedur_non_bedah',
            'Prosedur Bedah' => 'prosedur_bedah',
            'Konsultasi' => 'konsultasi',
            'Tenaga Ahli' => 'tenaga_ahli',
            'Keperawatan' => 'keperawatan',
            'Penunjang' => 'penunjang',
            'Radiologi' => 'radiologi',
            'Laboratorium' => 'laboratorium',
            'Pelayanan Darah' => 'pelayanan_darah',
            'Rehabilitasi' => 'rehabilitasi',
            'Kamar / Akomodasi' => 'kamar',
            'Obat' => 'obat',
            'Alkes' => 'alkes',
            'BMHP' => 'bmhp',
            'Sewa Alat' => 'sewa_alat',
        ];
        foreach ($petaMapping as $jenis => $kolom) {
            $tarif[$kolom] += (float) ($mapping[$jenis] ?? 0);
        }

        $sum = static function (string $table, string $expression, callable $filter) use ($noreg): float {
            $query = DB::table($table)->whereIn($table.'.rs1', $noreg);
            $filter($query);
            return (float) ($query->selectRaw("COALESCE(SUM($expression), 0) as total")->value('total') ?? 0);
        };
        $tanpaFilter = static fn ($query) => $query;

        $tarif['prosedur_non_bedah'] += $sum('rs246', 'rs5', $tanpaFilter);
        $tarif['prosedur_non_bedah'] += $sum('rs73', '(rs7 + rs13) * rs5', fn ($q) => $q->whereIn('rs22', ['POL031', 'PEN005']));

        $tarif['prosedur_bedah'] += $sum('rs54', '(rs5 + rs6 + rs7) * rs8', $tanpaFilter);
        $tarif['prosedur_bedah'] += $sum('rs73', '(rs7 + rs13) * rs5', fn ($q) => $q->whereIn('rs22', ['OPERASI', 'OPERASI2', 'OPERASIIRD2', 'OPERASIIRD']));
        $tarif['prosedur_bedah'] += $sum('rs226', '(rs5 + rs6 + rs7) * rs8', function ($q) use ($isRanap) {
            if (!$isRanap) $q->where('rs15', 'POL014');
        });

        if ($isRanap) {
            $tarif['konsultasi'] += $sum('rs140', 'rs4 + rs5', $tanpaFilter);
            $tarif['konsultasi'] += $sum('rs202', 'rs4 + rs5', fn ($q) => $q->whereIn('rs3', ['K00013', 'K00004', 'K00003']));
            $tarif['keperawatan'] += $sum('rs203', 'rs4 + rs5', $tanpaFilter);
        } else {
            $tarif['konsultasi'] += $sum('rs35', 'rs7 + rs11', fn ($q) => $q->whereIn('rs3', ['K2#', 'K3#']));
        }
        $tarif['tenaga_ahli'] += $sum('rs73', '(rs7 + rs13) * rs5', fn ($q) => $q->where('rs22', 'FISIO'));
        $tarif['penunjang'] += $sum('rs73', '(rs7 + rs13) * rs5', fn ($q) => $q->whereIn('rs22', ['POL024', 'POL026']));
        $tarif['penunjang'] += (float) DB::table('psikologi_trans')
            ->whereIn('rs1', $noreg)
            ->selectRaw('COALESCE(SUM((rs7 + rs13) * rs5), 0) as total')
            ->value('total');
        $tarif['pelayanan_darah'] += $sum('rs231', 'rs12 + rs13', $tanpaFilter);
        $tarif['radiologi'] += $sum('rs48', '(rs6 + rs8) * rs24', $tanpaFilter);
        if ($isRanap) {
            $tarif['laboratorium'] += (float) DB::table('tapheresis')->where('noreg', $noregUtama)->selectRaw('COALESCE(SUM(js + jp), 0) as total')->value('total');
        }


        $tarif['laboratorium'] += (float) DB::query()->fromSub(
            DB::table('rs51')
                ->join('rs49', 'rs49.rs1', '=', 'rs51.rs4')
                ->selectRaw("CASE WHEN rs49.rs21 = '' THEN CONCAT('D-', rs51.id) ELSE CONCAT('G-', rs51.rs1, '-', rs49.rs21) END as grup")
                ->selectRaw('SUM((rs51.rs6 + rs51.rs13) * rs51.rs5) as subtotal')
                ->whereIn('rs51.rs1', $noreg)
                ->where(function ($q) {
                    $q->where('rs51.rs23', '<>', 'POL014')
                        ->orWhere(function ($q) {
                            $q->where('rs51.rs23', 'POL014')->where('rs51.rs26', '1')->where('rs51.lunas', '<>', '1');
                        });
                })
                ->groupBy('grup'),
            'lab'
        )->sum('subtotal');

        if ($isRanap) {
            $tarif['kamar'] += $sum('rs35x', 'rs7 + rs14', fn ($q) => $q->whereRaw("LOWER(rs3) = 'k1#'")->whereNotIn('rs17', ['IC', 'ICC']));
            $tarif['kamar'] += $sum('rs35x', 'rs7', fn ($q) => $q->where('rs3', 'A2#'));
            $tarif['rawat_intensif'] += $sum('rs35x', 'rs7 + rs14', fn ($q) => $q->whereRaw("LOWER(rs3) = 'k1#'")->whereIn('rs17', ['IC', 'ICC']));
        } else {
            $tarif['kamar'] += $sum('rs35', 'rs35.rs7 + rs35.rs11', function ($q) {
                $q->join('rs17', 'rs17.rs1', '=', 'rs35.rs1')->whereIn('rs35.rs3', ['RM#', 'K1#'])
                    ->whereNotIn('rs17.rs8', ['PEN003', 'PEN004', 'PEN005', 'PEN006', 'POL031', 'POL030', 'POL029', 'POL026', 'POL024']);
            });
            $tarif['kamar'] += $sum('rs35x', 'rs7', fn ($q) => $q->where('rs3', 'A2#'));
        }

        [$obatFarmasi, $alkesFarmasi] = $this->tarifFarmasiRajal($noreg);
        $tarif['obat'] += $obatFarmasi;
        $tarif['alkes'] += $alkesFarmasi;

        return new JsonResponse(array_merge($this->denganTotalTarif($tarif), ['layanan' => $isRanap ? 'ranap' : 'rajal']));
    }

    private function tarifFarmasiRajal(array $noreg): array
    {
        $hitung = function (bool $alkes) use ($noreg): float {
            $operator = $alkes ? '=' : '<>';
            $jenis = 'Alkes Habis Pakai';
            $resep = DB::connection('farmasi')->table('resep_keluar_h as h')
                ->leftJoin('resep_keluar_r as r', 'h.noresep', '=', 'r.noresep')
                ->leftJoin('new_masterobat as m', 'r.kdobat', '=', 'm.kd_obat')
                ->whereIn('h.noreg', $noreg)
                ->where('m.jenis_perbekalan', $operator, $jenis)
                ->selectRaw('COALESCE(SUM((r.harga_jual * r.jumlah) + r.nilai_r), 0) as total')
                ->value('total');
            $racikan = DB::connection('farmasi')->table('resep_keluar_h as h')
                ->leftJoin('resep_keluar_racikan_r as r', 'h.noresep', '=', 'r.noresep')
                ->leftJoin('new_masterobat as m', 'r.kdobat', '=', 'm.kd_obat')
                ->whereIn('h.noreg', $noreg)
                ->where('m.jenis_perbekalan', $operator, $jenis)
                ->selectRaw('COALESCE(SUM((r.harga_jual * r.jumlah) + r.nilai_r), 0) as total')
                ->value('total');
            $retur = DB::connection('farmasi')->table('retur_penjualan_h as h')
                ->leftJoin('retur_penjualan_r as r', 'r.noretur', '=', 'h.noretur')
                ->leftJoin('new_masterobat as m', 'r.kdobat', '=', 'm.kd_obat')
                ->whereIn('h.noreg', $noreg)
                ->where('m.jenis_perbekalan', $operator, $jenis)
                ->selectRaw('COALESCE(SUM((r.jumlah_retur * r.harga_jual) + r.nilai_r), 0) as total')
                ->value('total');

            return (float) $resep + (float) $racikan - (float) $retur;
        };

        return [$hitung(false), $hitung(true)];
    }

    private function denganTotalTarif(array $tarif): array
    {
        $tarif = array_map(static fn ($nilai) => round((float) $nilai), $tarif);
        $tarif['total_tarif'] = array_sum($tarif);

        return $tarif;
    }

    public function getdataklaim()
    {
        $pelayanan = request('pelayanan');
        $bulan = request('bulan');
        $tahun = request('tahun');
        if($pelayanan === '1')
        {
            $kdpoli = ['POL014'];
        }else{
            $kdpoli = Mpoli::select('rs1')->where('rs1', '!=', 'POL014')->get();

        }

            $data = listcasmixrajal::select('listkirimcasmixRajal.noreg as noreg','listkirimcasmixRajal.norm as norm',
            'listkirimcasmixRajal.nosep as nosep','listkirimcasmixRajal.noka as noka',
            'listkirimcasmixRajal.norm as norm','listkirimcasmixRajal.nosep as nosep',
            'kepegx.pegawai.nama as dokter','rs17.rs3 as tgl_kunjungan','rs17.rs8 as kodepoli',
            'rs15.rs2 as pasien',
            'rs15.rs49 as nktp',
            'rs15.rs55 as nohp',
            'rs15.rs16 as tgllahir',
             'rs15.rs17 as kelamin',
             'rs17.rs26 as tglpulang',
             DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
             DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
            TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
            TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
             'rs9.rs2 as sistembayar',
            'rs19.rs2 as poli','klaim_trans_rajal.status_klaim as ket',
            DB::raw('\'rajal\' as layanan'))
            ->leftjoin('rs17', 'rs17.rs1', '=', 'listkirimcasmixRajal.noreg')
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2')
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8')
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14')
            ->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs17.rs9')
            ->leftjoin('klaim_trans_rajal', 'klaim_trans_rajal.noreg', '=', 'listkirimcasmixRajal.noreg')
            ->whereYear('rs17.rs3', $tahun )->whereMonth('rs17.rs3', $bulan)->whereIn('kodepoli',$kdpoli)
            ->where('rs9.groups', '1')
            ->where(function ($query) {
                $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('listkirimcasmixRajal.nosep', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
            })
            ->paginate(request('per_page'));
            return new JsonResponse($data);
        // }else{
        //     $data = listcasmixrajal::select('listkirimcasmixRajal.noreg as noreg','listkirimcasmixRajal.norm as norm',
        //     'listkirimcasmixRajal.nosep as nosep','listkirimcasmixRajal.noka as noka',
        //     'listkirimcasmixRajal.norm as norm','listkirimcasmixRajal.nosep as nosep',
        //     'kepegx.pegawai.nama as dokter','rs17.rs3 as tgl_kunjungan','rs17.rs8 as kodepoli',
        //     'rs15.rs2 as pasien',
        //     'rs15.rs49 as nktp',
        //     'rs15.rs55 as nohp',
        //      'rs15.rs17 as kelamin',
        //      'rs17.rs26 as tglpulang',
        //      DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
        //      DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
        //     TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
        //     TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
        //      'rs9.rs2 as sistembayar',
        //     'rs19.rs2 as poli','klaim_trans_rajal.status_klaim as ket',
        //     DB::raw('\'rajal\' as layanan'))
        //     ->leftjoin('rs17', 'rs17.rs1', '=', 'listkirimcasmixRajal.noreg')
        //     ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2')
        //     ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8')
        //     ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14')
        //     ->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs17.rs9')
        //     ->leftjoin('klaim_trans_rajal', 'klaim_trans_rajal.noreg', '=', 'listkirimcasmixRajal.noreg')
        //     ->whereYear('rs17.rs3', $tahun )->whereMonth('rs17.rs3', $bulan)->where('kodepoli','!=','POL014')
        //     ->where('rs9.groups', '1')
        //     ->paginate(request('per_page'));
        //     return new JsonResponse($data);
        // }
    }
}
