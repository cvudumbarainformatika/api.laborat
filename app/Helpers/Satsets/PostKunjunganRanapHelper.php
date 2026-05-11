<?php

namespace App\Helpers\Satsets;

use App\Helpers\AuthSatsetHelper;
use App\Helpers\BridgingSatsetHelper;
use App\Models\Pasien;
use App\Models\Satset\SatsetErrorRespon;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostKunjunganRanapHelper
{
    public static function ranap()
    {
        // 1. Ambil tanggal 5 hari yang lalu
        $tglTarget = Carbon::now()->subDays(5)->toDateString();

        $query = Kunjunganranap::query();

        $select = $query->select(
            'rs23.rs1',
            'rs23.rs1 as noreg',
            'rs23.rs2 as norm',
            'rs23.rs3 as tglmasuk',
            'rs23.rs4 as tglkeluar',
            'rs23.rs5 as kdruangan',
            'rs23.rs5',
            'rs23.rs6 as ketruangan',
            'rs23.rs7 as nomorbed',
            'rs23.rs10 as kddokter',
            'rs23.rs10',
            'rs23.rs27',
            'rs21.rs2 as dokter',
            'rs23.rs19 as kodesistembayar', // ini untuk farmasi
            'rs23.rs22 as status', // '' : BELUM PULANG | '2 ato 3' : PASIEN PULANG
            'rs23.rs38 as hak_kelas',
            'rs15.rs2 as nama_panggil',

            DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
            DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                      TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                      TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            DB::raw("(IF(rs23.rs4='0000-00-00 00:00:00',datediff('" . date("Y-m-d") . "',rs23.rs3),
          datediff(rs23.rs4,rs23.rs3)))+1  as lama"),

            'rs15.rs4 as alamatbarcode',
            'rs15.rs16 as tgllahir',
            'rs15.rs17 as kelamin',
            'rs15.rs19 as pendidikan',
            'rs15.rs22 as agama',
            'rs15.rs37 as templahir',
            'rs15.rs39 as suku',
            'rs15.rs40 as jenispasien',
            'rs15.rs46 as noka',
            'rs15.rs49 as nik',
            'rs15.rs55 as nohp',
            'rs15.satset_uuid as pasien_uuid',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            'rs21.rs2 as namanakes',
            'rs24.rs2 as ruangan',
            'rs24.rs3 as kelasruangan',
            'rs24.rs5 as group_ruangan',
            'rs242.rs4 as tindaklanjut'
        )
            ->leftjoin('rs15', 'rs15.rs1', 'rs23.rs2')
            ->leftjoin('rs9', 'rs9.rs1', 'rs23.rs19')
            ->leftjoin('rs21', 'rs21.rs1', 'rs23.rs10')
            ->leftjoin('rs24', 'rs24.rs1', 'rs23.rs5')
            ->leftjoin('rs242', 'rs242.rs1', 'rs23.rs1') // rencana tindak lanjut
            // ->where('rs23.rs1', $noreg)
            ->with([
                'satset:uuid',
                'satset_error:uuid',
                'diagnosa' => function ($q) {
                    $q->select('rs101.rs1', 'rs101.rs3 as kode', 'rs99x.rs4 as inggris', 'rs99x.rs3 as indonesia', 'rs101.rs4 as type', 'rs101.rs7 as status', 'rs101.rs12 as recordedDate')
                        ->leftjoin('rs99x', 'rs101.rs3', 'rs99x.rs1')
                        ->orderBy('rs101.id', 'asc');
                },
                'datasimpeg:nik,nama,kelamin,kdpegsimrs,kddpjp,satset_uuid',
                'relmasterruangranap' => function ($q) {
                    $q->select('rs1', 'rs2 as nama', 'kode_ruang')->with('ruang:kode,uraian,groupper,gedung,lantai,satset_uuid,departement_uuid');
                },

                'radiologi' => function ($t) {
                    $t->with([
                        'rincians' => function ($r) {
                            $r->leftJoin('rs151', function ($join) {
                                $join->on('rs48.rs2', '=', 'rs151.rs5')
                                    ->on('rs48.rs1', '=', 'rs151.rs1')
                                    ->on('rs48.rs4', '=', 'rs151.kode');
                            })->leftJoin('rs48_pacs', 'rs48.rs2', '=', 'rs48_pacs.nota')
                                ->select('rs48.*', 'rs48_pacs.*', 'rs151.hasil', 'rs151.rs3 as kesimpulan', 'rs151.hasilhtml', 'rs151.kesimpulanhtml', 'rs151.rs4 as pelaksana');
                        },
                        'rincians.relmasterpemeriksaan',
                        'dokter:nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp,ttdpegawai',
                    ])->orderBy('id', 'DESC');
                },
            ])

            // ->where('rs23.rs1', $noreg)
            ->where('rs23.rs4', 'LIKE', $tglTarget . '%')
            ->whereIn('rs23.rs22', ['2', '3'])                                   // Status sudah pulang
            ->doesntHave('satset')
            ->doesntHave('satset_error')                                         // Belum terkirim
            ->orderBy('rs23.rs4', 'asc')

            ->first();

        return $select;
        // return self::kirimKunjunganRanap($select);
    }

    public static function kirimKunjunganRanap($data)
    {
        $pasien_uuid = $data->pasien_uuid;
        $practitioner_uuid = $data->datasimpeg ? $data->datasimpeg['satset_uuid'] : null;

        if (!$pasien_uuid) {
            $getPasienFromSatset = self::getPasienByNikSatset($data);
            $pasien_uuid = $getPasienFromSatset['data']['uuid'] ?? null;
        }

        if (!$practitioner_uuid) {
            $getFromSatset = self::getPractitionerFromSatset($data);
            $practitioner_uuid = $getFromSatset['data']['uuid'] ?? null;
        }

        if (!$pasien_uuid) {
            return ['message' => 'error', 'data' => 'Pasien UUID Tidak Ditemukan'];
        }

        $send = self::form($data, $pasien_uuid);
        if ($send['message'] === 'success') {
            $token = AuthSatsetHelper::accessToken();
            $send = BridgingSatsetHelper::post_bundle($token, $send['data'], $data->noreg);
        }
        return $send;
    }

    public static function getPasienByNikSatset($pasien)
    {
        // return $request->all();
        $nik = $pasien->nik;
        $norm = $pasien->norm;
        // get data ke satset
        $token = AuthSatsetHelper::accessToken();
        $params = '/Patient?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;

        $send = BridgingSatsetHelper::get_data($token, $params);

        $data = Pasien::where([
            ['rs49', $nik],
            ['rs1', $norm],
        ])->first();

        if ($send['message'] === 'success') {
            $data->satset_uuid = $send['data']['uuid'];
            $data->save();
        } else {
            SatsetErrorRespon::create([
                'uuid' => $pasien->noreg,
                'response' => $send
            ]);
        }
        return $send;
    }

    public static function getPractitionerFromSatset($pasien)
    {
        $nik = $pasien->datasimpeg ? $pasien->datasimpeg['nik'] : null;
        $token = AuthSatsetHelper::accessToken();
        $params = '/Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;

        $send = BridgingSatsetHelper::get_data($token, $params);

        $data = Pegawai::where('nik', $nik)->where('aktif', 'AKTIF')->first();

        if ($send['message'] === 'success') {
            $data->satset_uuid = $send['data']['uuid'];
            $data->save();
        } else {
            SatsetErrorRespon::create([
                'uuid' => $pasien->noreg,
                'response' => $send
            ]);
        }
        return $send;
    }


    public static function generateUuid()
    {
        return (string) Str::orderedUuid();
    }

    public static function form($request, $pasien_uuid)
    {
        $organization_id = BridgingSatsetHelper::organization_id();
        $encounter_uuid = "urn:uuid:" . self::generateUuid();

        $form = [
            "resourceType" => "Bundle",
            "type" => "transaction",
            "entry" => []
        ];

        // 1. Ambil data Encounter & Condition
        $res_ec = self::encounter($request, $pasien_uuid, $organization_id, $encounter_uuid);
        $form['entry'][] = $res_ec['encounter'];
        foreach ($res_ec['condition'] as $cond) {
            $form['entry'][] = $cond;
        }

        // 2. Tambahkan Radiologi (Menggunakan data dari $request)
        if (isset($request->radiologi) && count($request->radiologi) > 0) {
            $res_radiologi = self::radiologi($request, $pasien_uuid, $encounter_uuid, $organization_id);
            foreach ($res_radiologi as $rad_entry) {
                $form['entry'][] = $rad_entry;
            }
        }

        return ['message' => 'success', 'data' => $form];
    }


    static function encounter($request, $pasien_uuid, $organization_id, $encounter_uuid)
    {
        $start = Carbon::parse($request->tglmasuk)->toIso8601String();

        // Cek apakah pasien sudah pulang atau belum
        $tgl_keluar_raw = $request->tglkeluar;
        $is_pulang = ($tgl_keluar_raw && $tgl_keluar_raw != '0000-00-00 00:00:00' && !str_contains($tgl_keluar_raw, '-0001'));

        $status = $is_pulang ? 'finished' : 'in-progress';
        $end = $is_pulang ? Carbon::parse($tgl_keluar_raw)->toIso8601String() : null;

        // A. Persiapan Diagnosa (Hanya kirim jika ada data)
        $diagnosa_entries = [];
        $condition_entries = [];

        $diagnosas = $request->diagnosa ?? [];

        foreach ($diagnosas as $key => $val) {
            $cond_uuid = "urn:uuid:" . self::generateUuid();
            $diagnosa_entries[] = [
                "condition" => ["reference" => $cond_uuid, "display" => $val['inggris'] ?? $val['indonesia'] ?? 'Diagnosis'],
                "use" => ["coding" => [["system" => "http://terminology.hl7.org/CodeSystem/diagnosis-role", "code" => "DD", "display" => "Discharge diagnosis"]]],
                "rank" => $key + 1
            ];
            $condition_entries[] = [
                "fullUrl" => $cond_uuid,
                "resource" => [
                    "resourceType" => "Condition",
                    "clinicalStatus" => ["coding" => [["system" => "http://terminology.hl7.org/CodeSystem/condition-clinical", "code" => "active"]]],
                    "category" => [["coding" => [["system" => "http://terminology.hl7.org/CodeSystem/condition-category", "code" => "encounter-diagnosis"]]]],
                    "code" => [
                        "coding" => [["system" => "http://hl7.org/fhir/sid/icd-10", "code" => $val['kode'], "display" => $val['inggris'] ?? $val['indonesia'] ?? 'Diagnosis']],
                        "text" => $val['indonesia'] ?? $val['inggris'] ?? 'Diagnosis'
                    ],
                    "subject" => ["reference" => "Patient/$pasien_uuid"],
                    "encounter" => ["reference" => $encounter_uuid],
                    "onsetDateTime" => $start,
                ],
                "request" => ["method" => "POST", "url" => "Condition"]
            ];
        }

        // B. Data Lokasi (Bangsal)
        $relmasterRuang = $request->relmasterruangranap['ruang'] ?? null;
        $ruangId = $relmasterRuang['satset_uuid'] ?? null;
        $lantai = $relmasterRuang['lantai'] ?? '-';
        $gedung = $relmasterRuang['gedung'] ?? '-';

        // C. Perakit Resource Encounter (IMP)
        $formEncounter = [
            "fullUrl" => $encounter_uuid,
            "resource" => [
                "resourceType" => "Encounter",
                "identifier" => [["system" => "http://sys-ids.kemkes.go.id/encounter/$organization_id", "value" => $request->noreg]],
                "status" => $status,
                "class" => ["system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode", "code" => "IMP", "display" => "inpatient encounter"],
                "subject" => ["reference" => "Patient/$pasien_uuid", "display" => $request->nama_panggil],
                "participant" => [[
                    "type" => [["coding" => [["system" => "http://terminology.hl7.org/CodeSystem/v3-ParticipationType", "code" => "ATND", "display" => "attender"]]]],
                    "individual" => [
                        "reference" => "Practitioner/" . ($request->datasimpeg['satset_uuid'] ?? '-'),
                        "display" => $request->datasimpeg['nama'] ?? '-'
                    ]
                ]],
                "period" => ["start" => $start],
                "statusHistory" => [
                    ["status" => "in-progress", "period" => ["start" => $start, "end" => ($end ?? $start)]]
                ],
                "diagnosis" => $diagnosa_entries,
                "serviceProvider" => ["reference" => "Organization/$organization_id"],
            ],
            "request" => ["method" => "POST", "url" => "Encounter"],
        ];

        // Tambahkan end period jika sudah pulang
        if ($is_pulang && $end) {
            $formEncounter['resource']['period']['end'] = $end;
            $formEncounter['resource']['statusHistory'][] = ["status" => "finished", "period" => ["start" => $end, "end" => $end]];
            $formEncounter['resource']['hospitalization'] = [
                "dischargeDisposition" => [
                    "coding" => [["system" => "http://terminology.hl7.org/CodeSystem/discharge-disposition", "code" => "home", "display" => "Home"]],
                    "text" => "Anjuran dokter untuk pulang"
                ]
            ];
        }

        // D. Tambahkan Location (Wajib untuk Ranap)
        $kelasMapping = [
            'VVIP' => 'vip',
            'VIP' => 'vip',
            '1' => '1',
            '2' => '2',
            '3' => '3'
        ];
        $kodeKelasSS = $kelasMapping[$request->kelasruangan] ?? '3';

        $loc_entry = [
            "extension" => [[
                "extension" => [
                    [
                        "url" => "value",
                        "valueCodeableConcept" => ["coding" => [["system" => "http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Inpatient", "code" => $kodeKelasSS, "display" => "Kelas $request->kelasruangan"]]]
                    ],
                    [
                        "url" => "upgradeClassIndicator",
                        "valueCodeableConcept" => ["coding" => [["system" => "http://terminology.kemkes.go.id/CodeSystem/locationUpgradeClass", "code" => "kelas-tetap", "display" => "Kelas Tetap Perawatan"]]]
                    ]
                ],
                "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass"
            ]],
            "location" => [
                "reference" => "Location/" . ($ruangId && $ruangId != '-' ? $ruangId : '{{Location_Ruang_Default_id}}'),
                "display" => "Bed $request->nomorbed, $request->ruangan, Lantai $lantai Gedung $gedung"
            ],
            "period" => ["start" => $start]
        ];
        if ($is_pulang && $end) {
            $loc_entry['period']['end'] = $end;
        }
        $formEncounter['resource']['location'] = [$loc_entry];

        return ["encounter" => $formEncounter, "condition" => $condition_entries];
    }

    static function radiologi($request, $pasien_uuid, $encounter_uuid, $organization_id)
    {
        $entries = [];
        $radiologis = $request->radiologi ?? [];

        foreach ($radiologis as $rad) {
            $nota_simrs = $rad['rs2'];
            $diagnosa_klinis = $rad['diagnosakerja'] ?? 'Permintaan Foto';

            foreach ($rad['rincians'] as $rincian) {
                $modality = $rincian['relmasterpemeriksaan']['modality'] ?? 'CR';
                $nama_foto = $rincian['relmasterpemeriksaan']['rs2'] ?? $rincian['pemeriksaan'] ?? '-';
                $study_uid = $rincian['study_instance_uid'] ?? null;
                $hasil_expertise = $rincian['hasil'] ?? null;

                $loinc_code = $rincian['relmasterpemeriksaan']['loinc_code'] ?? '24648-8';
                $loinc_display = $rincian['relmasterpemeriksaan']['loinc_display'] ?? 'Chest XR';

                // 1. ServiceRequest (ORDER)
                $servisRequest_uuid = "urn:uuid:" . self::generateUuid();
                $entries[] = [
                    "fullUrl" => $servisRequest_uuid,
                    "resource" => [
                        "resourceType" => "ServiceRequest",
                        "identifier" => [
                            ["system" => "http://sys-ids.kemkes.go.id/servicerequest/" . $organization_id, "value" => "ORD-" . $nota_simrs . "-" . ($rincian['id'] ?? self::generateUuid())],
                            [
                                "use" => "usual",
                                "type" => ["coding" => [["system" => "http://terminology.hl7.org/CodeSystem/v2-0203", "code" => "ACSN"]]],
                                "system" => "http://sys-ids.kemkes.go.id/acsn/" . $organization_id,
                                "value" => $nota_simrs
                            ]
                        ],
                        "status" => "active",
                        "intent" => "order",
                        "category" => [["coding" => [["system" => "http://snomed.info/sct", "code" => "363679005", "display" => "Imaging procedure"]]]],
                        "code" => ["coding" => [["system" => "http://loinc.org", "code" => $loinc_code, "display" => $loinc_display]], "text" => $nama_foto],
                        "subject" => ["reference" => "Patient/" . $pasien_uuid],
                        "encounter" => ["reference" => $encounter_uuid],
                        "occurrenceDateTime" => Carbon::parse($rad['rs3'])->toIso8601String(),
                        "requester" => ["reference" => "Practitioner/" . ($request->datasimpeg['satset_uuid'] ?? '-')],
                        "performer" => [["reference" => "Organization/" . $organization_id]],
                        "reasonCode" => [["text" => $diagnosa_klinis]],
                    ],
                    "request" => ["method" => "POST", "url" => "ServiceRequest"],
                ];

                // 2. ImagingStudy
                $imagingStudy_uuid = null;
                if ($study_uid && $study_uid != "NULL") {
                    $imagingStudy_uuid = "urn:uuid:" . self::generateUuid();
                    $entries[] = [
                        "fullUrl" => $imagingStudy_uuid,
                        "resource" => [
                            "resourceType" => "ImagingStudy",
                            "identifier" => [
                                ["system" => "http://sys-ids.kemkes.go.id/imagingstudy/" . $organization_id, "value" => $nota_simrs],
                                [
                                    "use" => "usual",
                                    "type" => ["coding" => [["system" => "http://terminology.hl7.org/CodeSystem/v2-0203", "code" => "ACSN"]]],
                                    "system" => "http://sys-ids.kemkes.go.id/acsn/" . $organization_id,
                                    "value" => $nota_simrs
                                ],
                                [
                                    "system" => "urn:dicom:uid",
                                    "value" => "urn:oid:" . $study_uid
                                ]
                            ],
                            "status" => "available",
                            "subject" => ["reference" => "Patient/" . $pasien_uuid],
                            "encounter" => ["reference" => $encounter_uuid],
                            "basedOn" => [["reference" => $servisRequest_uuid]],
                            "started" => Carbon::parse($rincian['created_at'] ?? $rad['rs3'])->toIso8601String(),
                            "modality" => [["system" => "http://dicom.nema.org/resources/ontology/DCM", "code" => $modality]],
                            "series" => [["uid" => $study_uid, "modality" => ["system" => "http://dicom.nema.org/resources/ontology/DCM", "code" => $modality]]]
                        ],
                        "request" => ["method" => "POST", "url" => "ImagingStudy"],
                    ];
                }

                // 3. Observation & DiagnosticReport (Expertise)
                if ($hasil_expertise) {
                    $observation_uuid = "urn:uuid:" . self::generateUuid();
                    $entries[] = [
                        "fullUrl" => $observation_uuid,
                        "resource" => [
                            "resourceType" => "Observation",
                            "status" => "final",
                            "category" => [["coding" => [["system" => "http://terminology.hl7.org/CodeSystem/observation-category", "code" => "imaging", "display" => "Imaging"]]]],
                            "code" => ["coding" => [["system" => "http://loinc.org", "code" => $loinc_code, "display" => $loinc_display]]],
                            "subject" => ["reference" => "Patient/" . $pasien_uuid],
                            "encounter" => ["reference" => $encounter_uuid],
                            "effectiveDateTime" => Carbon::parse($rincian['updated_at'] ?? $rad['rs3'])->toIso8601String(),
                            "performer" => [["reference" => "Practitioner/" . ($request->datasimpeg['satset_uuid'] ?? '-')]],
                            "valueString" => $hasil_expertise
                        ],
                        "request" => ["method" => "POST", "url" => "Observation"],
                    ];

                    $entries[] = [
                        "fullUrl" => "urn:uuid:" . self::generateUuid(),
                        "resource" => [
                            "resourceType" => "DiagnosticReport",
                            "status" => "final",
                            "category" => [["coding" => [["system" => "http://terminology.hl7.org/CodeSystem/v2-0074", "code" => "RAD", "display" => "Radiology"]]]],
                            "code" => ["coding" => [["system" => "http://loinc.org", "code" => $loinc_code, "display" => $loinc_display]]],
                            "subject" => ["reference" => "Patient/" . $pasien_uuid],
                            "encounter" => ["reference" => $encounter_uuid],
                            "effectiveDateTime" => Carbon::parse($rincian['updated_at'] ?? $rad['rs3'])->toIso8601String(),
                            "issued" => Carbon::parse($rincian['updated_at'] ?? $rad['rs3'])->toIso8601String(),
                            "performer" => [["reference" => "Organization/" . $organization_id]],
                            "basedOn" => [["reference" => $servisRequest_uuid]],
                            "result" => [["reference" => $observation_uuid]],
                            "imagingStudy" => $imagingStudy_uuid ? [["reference" => $imagingStudy_uuid]] : [],
                            "conclusion" => $hasil_expertise,
                        ],
                        "request" => ["method" => "POST", "url" => "DiagnosticReport"],
                    ];
                }
            }
        }
        return $entries;
    }
}
