<?php

namespace App\Http\Controllers\Api\Simrs\Penjaminan;

use App\Http\Controllers\Controller;
use App\Helpers\Eklaim\Eklaim;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Penjaminan\listcasmixrajal;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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


    public function newClaim(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noPeserta' => ['required', 'string', 'max:200'],
            'noSep' => ['nullable', 'string', 'max:200'],
            'nomor_rm' => ['required', 'string', 'max:100'],
            'nama_pasien' => ['required', 'string', 'max:200'],
            'tgl_lahir' => ['required', 'date'],
            'gender' => ['required', 'in:1,2'],
        ]);

        $queryNewClaim = [
            'metadata' => ['method' => 'new_claim'],
            'data' => [
                'nomor_kartu' => $validated['noPeserta'],
                'nomor_sep' => $validated['noSep'] ?? '',
                'nomor_rm' => $validated['nomor_rm'],
                'nama_pasien' => $validated['nama_pasien'],
                'tgl_lahir' => $validated['tgl_lahir'],
                'gender' => $validated['gender'],
            ],
        ];

        return new JsonResponse(Eklaim::curl_func($queryNewClaim));
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

        return new JsonResponse([
            'data' => $kunjungan,
            'covid19' => $covid19,
            'sudahpernahklaim' => $sudahPernahKlaim,
            'total_tarif' => 0,
            'layanan' => 'ranap',
        ]);
    }

    private function kunjunganKlaimRajal(string $noreg): JsonResponse
    {
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

        return new JsonResponse([
            'data' => $kunjungan,
            'covid19' => $covid19,
            'sudahpernahklaim' => $sudahPernahKlaim,
            'total_tarif' => 0,
            'flagidrg' => DB::table('idrg_klaim')->where('noreg', trim($noreg))->exists() ? 1 : 0,
            'layanan' => 'rajal',
        ]);
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
