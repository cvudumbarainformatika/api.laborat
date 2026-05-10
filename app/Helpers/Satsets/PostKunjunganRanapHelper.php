<?php

namespace App\Helpers\Satsets;

use App\Helpers\BridgingSatsetHelper;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostKunjunganRanapHelper
{
    public static function ranap($noreg)
    {
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
            // 'rs222.rs8 as sep_igd',
            // 'rs227.rs8 as sep',
            // 'rs227.rs10 as faskesawal',
            // 'rs227.kodedokterdpjp as kodedokterdpjp',
            // 'rs227.dokterdpjp as dokterdpjp',
            'rs24.rs2 as ruangan',
            'rs24.rs3 as kelasruangan',
            'rs24.rs5 as group_ruangan',
            // 'rs101.rs3 as kode_diagnosa'
            // 'bpjs_spri.noSuratKontrol as noSpri'
            'rs242.rs4 as tindaklanjut'
        )
            ->leftjoin('rs15', 'rs15.rs1', 'rs23.rs2')
            ->leftjoin('rs9', 'rs9.rs1', 'rs23.rs19')
            ->leftjoin('rs21', 'rs21.rs1', 'rs23.rs10')
            ->leftjoin('rs24', 'rs24.rs1', 'rs23.rs5')
            ->leftjoin('rs242', 'rs242.rs1', 'rs23.rs1') // rencana tindak lanjut
            // ->leftjoin('rs227', 'rs227.rs1', 'rs23.rs1')
            // ->leftjoin('rs222', 'rs222.rs1', 'rs23.rs1')
            // ->leftjoin('rs101', 'rs101.rs1', 'rs23.rs1')
            // ->leftjoin('bpjs_spri', 'rs23.rs1', '=', 'bpjs_spri.noreg')

            // ->with(['sepranap' => function($q) {
            //     $q->select('rs1', 'rs8 as noSep', 'rs3 as ruang', 'rs5 as noRujukan', 'rs7 as diagnosa', 'rs10 as ppkRujukan', 'rs11 as jenisPeserta');
            // }])

            ->where('rs23.rs1', $noreg)

            ->with([
                'diagnosa' => function ($q) {
                    $q->select('rs101.rs1', 'rs101.rs3 as kode', 'rs99x.rs4 as inggris', 'rs99x.rs3 as indonesia', 'rs101.rs4 as type', 'rs101.rs7 as status', 'rs101.rs12 as recordedDate')
                        ->leftjoin('rs99x', 'rs101.rs3', 'rs99x.rs1')
                        ->orderBy('rs101.id', 'asc');
                },
                'datasimpeg:nik,nama,kelamin,kdpegsimrs,kddpjp,satset_uuid',
                'relmasterruangranap' => function ($q) {
                    $q->select('rs1', 'rs2 as nama', 'kode_ruang')->with('ruang:kode,uraian,groupper,gedung,lantai,satset_uuid,departement_uuid');
                }

            ])

            // ->where('rs23.rs1', $noreg)
            ->whereIn('rs23.rs22', ['2', '3'])
            ->first();

        return $select;
        // return self::kirimKunjunganRanap($select);
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

        // 2. Tambahkan Radiologi (Jika ada)
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
        $end = Carbon::parse($request->tglkeluar)->toIso8601String();

        // A. Persiapan Diagnosa
        $diagnosa_entries = [];
        $condition_entries = [];
        foreach ($request->diagnosa as $key => $val) {
            $cond_uuid = "urn:uuid:" . self::generateUuid();
            $diagnosa_entries[] = [
                "condition" => ["reference" => $cond_uuid, "display" => $val['inggris']],
                "use" => ["coding" => [["system" => "http://terminology.hl7.org/CodeSystem/diagnosis-role", "code" => "DD", "display" => "Discharge diagnosis"]]],
                "rank" => $key + 1
            ];
            $condition_entries[] = [
                "fullUrl" => $cond_uuid,
                "resource" => [
                    "resourceType" => "Condition",
                    "clinicalStatus" => ["coding" => [["system" => "http://terminology.hl7.org/CodeSystem/condition-clinical", "code" => "active"]]],
                    "category" => [["coding" => [["system" => "http://terminology.hl7.org/CodeSystem/condition-category", "code" => "encounter-diagnosis"]]]],
                    "code" => ["coding" => [["system" => "http://hl7.org/fhir/sid/icd-10", "code" => $val['kode'], "display" => $val['inggris']]]],
                    "subject" => ["reference" => "Patient/$pasien_uuid"],
                    "encounter" => ["reference" => $encounter_uuid],
                    "onsetDateTime" => $start,
                ],
                "request" => ["method" => "POST", "url" => "Condition"]
            ];
        }

        // B. Data Lokasi (Bangsal)
        $relmasterRuang = $request->relmasterruangranap['ruang'] ?? null;
        $ruangId = $relmasterRuang['satset_uuid'] ?? '-';
        $lantai = $relmasterRuang['lantai'] ?? '-';
        $gedung = $relmasterRuang['gedung'] ?? '-';

        // C. Perakit Resource Encounter (IMP)
        $formEncounter = [
            "fullUrl" => $encounter_uuid,
            "resource" => [
                "resourceType" => "Encounter",
                "identifier" => [["system" => "http://sys-ids.kemkes.go.id/encounter/$organization_id", "value" => $request->noreg]],
                "status" => "finished",
                "statusHistory" => [
                    ["status" => "in-progress", "period" => ["start" => $start, "end" => $end]],
                    ["status" => "finished", "period" => ["start" => $end, "end" => $end]]
                ],
                "class" => ["system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode", "code" => "IMP", "display" => "inpatient encounter"],
                "subject" => ["reference" => "Patient/$pasien_uuid", "display" => $request->nama_panggil],
                "participant" => [[
                    "type" => [["coding" => [["system" => "http://terminology.hl7.org/CodeSystem/v3-ParticipationType", "code" => "ATND", "display" => "attender"]]]],
                    "individual" => [
                        "reference" => "Practitioner/" . ($request->datasimpeg['satset_uuid'] ?? '-'),
                        "display" => $request->datasimpeg['nama'] ?? '-'
                    ]
                ]],
                "period" => ["start" => $start, "end" => $end],
                "diagnosis" => $diagnosa_entries,
                "hospitalization" => [
                    "dischargeDisposition" => [
                        "coding" => [["system" => "http://terminology.hl7.org/CodeSystem/discharge-disposition", "code" => "home", "display" => "Home"]],
                        "text" => "Anjuran dokter untuk pulang"
                    ]
                ],
                "location" => [[
                    "extension" => [[
                        "extension" => [
                            ["url" => "value", "valueCodeableConcept" => ["coding" => [["system" => "http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Inpatient", "code" => "$request->kelasruangan", "display" => "Kelas $request->kelasruangan"]]]],
                            ["url" => "upgradeClassIndicator", "valueCodeableConcept" => ["coding" => [["system" => "http://terminology.kemkes.go.id/CodeSystem/locationUpgradeClass", "code" => "kelas-tetap", "display" => "Kelas Tetap Perawatan"]]]]
                        ],
                        "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass"
                    ]],
                    "location" => [
                        "reference" => "Location/" . $ruangId,
                        "display" => "Bed $request->nomorbed, $request->ruangan, Lantai $lantai Gedung $gedung"
                    ],
                    "period" => ["start" => $start, "end" => $end]
                ]],
                "serviceProvider" => ["reference" => "Organization/$organization_id"],
            ],
            "request" => ["method" => "POST", "url" => "Encounter"],
        ];

        return ["encounter" => $formEncounter, "condition" => $condition_entries];
    }

    static function radiologi($request, $pasien_uuid, $encounter_uuid, $organization_id)
    {
        $entries = [];
        foreach ($request->radiologi as $rad) {
            foreach ($rad['rincians'] as $r) {
                $sr_uuid = "urn:uuid:" . self::generateUuid();
                // ServiceRequest
                $entries[] = [
                    "fullUrl" => $sr_uuid,
                    "resource" => [
                        "resourceType" => "ServiceRequest",
                        "identifier" => [["system" => "http://sys-ids.kemkes.go.id/servicerequest/$organization_id", "value" => $rad['nota']]],
                        "status" => "active",
                        "intent" => "original-order",
                        "code" => ["coding" => [["system" => "http://loinc.org", "code" => $r['loinc'] ?? '36660-0', "display" => $r['pemeriksaan']]]],
                        "subject" => ["reference" => "Patient/$pasien_uuid"],
                        "encounter" => ["reference" => $encounter_uuid],
                        "requester" => ["reference" => "Practitioner/" . ($request->datasimpeg['satset_uuid'] ?? '-')]
                    ],
                    "request" => ["method" => "POST", "url" => "ServiceRequest"]
                ];
                // ImagingStudy (Hanya jika ada study_id)
                if (!empty($rad['study_id'])) {
                    $entries[] = [
                        "resource" => [
                            "resourceType" => "ImagingStudy",
                            "status" => "available",
                            "subject" => ["reference" => "Patient/$pasien_uuid"],
                            "encounter" => ["reference" => $encounter_uuid],
                            "started" => Carbon::parse($rad['tgl_pemeriksaan'])->toIso8601String(),
                            "series" => [[
                                "uid" => $rad['study_instance_uid'],
                                "modality" => ["system" => "http://dicom.nema.org/resources/ontology/DCM", "code" => $rad['modality'] ?? 'CT'],
                                "instance" => [["uid" => $rad['study_instance_uid'] . ".1"]]
                            ]]
                        ],
                        "request" => ["method" => "POST", "url" => "ImagingStudy"]
                    ];
                }
            }
        }
        return $entries;
    }
}
