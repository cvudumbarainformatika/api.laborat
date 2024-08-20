<?php

namespace App\Helpers\Satsets;

use App\Helpers\BridgingSatsetHelper;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PostKunjunganRajalHelper
{
    public static function generateUuid()
    {
        return (string) Str::orderedUuid();
    }

    public static function form($request, $pasien_uuid)
    {
        $send = [
            'message' =>  'failed',
            'data' => null
        ];

        $encounter = self::generateUuid();

        $practitioner = $request->datasimpeg['satset_uuid'];

        $taskid = collect($request->taskid);
        if (count($taskid) === 0) {
            $send['data'] = 'data taskid dari request kosong';
            return $send;
        }

        $task3 = $taskid->filter(function ($item) {
          return $item['taskid'] === '3';
        })->first();
        $task4 = $taskid->filter(function ($item) {
            return $item['taskid'] === '4';
        })->first();
        $task5 = $taskid->filter(function ($item) {
            return $item['taskid'] === '5';
        })->first();

        if (!$task3 || !$task4 || !$task5) {
            
            $send['data'] = 'TASK iD Tdk lengkap';
            return $send;
        }

        $antri = Carbon::parse($task3['created_at'])->toIso8601String();
        $start = Carbon::parse($task4['created_at'])->toIso8601String();
        $end = Carbon::parse($task5['created_at'])->toIso8601String();

        setlocale(LC_ALL, 'IND');
        $dt = Carbon::parse($request->tgl_kunjungan)->locale('id');
        $dt->settings(['formatFunction' => 'translatedFormat']);
        $tgl_kunjungan = $dt->format('l, j F Y');
        // $tgl_kunjungan = $dt->format('l, j F Y ; h:i a');

        $rajal_org = '4b8fb632-6435-4fc1-8ea0-7aacc39974d6';
        $organization_id = BridgingSatsetHelper::organization_id();


        // DIAGNOSA

        $diagnosa = [];
        foreach ($request->diagnosa as $key => $value) {
            $uuid = self::generateUuid();
            $data = [
                "condition" => [
                    "reference" => "urn:uuid:$uuid",
                    "display" => $value['masterdiagnosa']['rs4']
                ],
                "use" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                            "code" => "DD",
                            "display" => "Discharge diagnosis"
                        ]
                    ]
                ],
                "rank" => $key + 1
            ];

            $diagnosa[] = $data;
        }


        // return $antri;
        #Bundle #1

        $relmasterRuang = $request->relmpoli['ruang'];
        $ruangId = !$relmasterRuang ? '-': $relmasterRuang['satset_uuid'] ?? '-';
        $ruang = !$relmasterRuang ? '-': $relmasterRuang['ruang'] ?? '-';
        $lantai = !$relmasterRuang ? '-': $relmasterRuang['lantai'] ?? '-';
        $gedung = !$relmasterRuang ? '-': $relmasterRuang['gedung'] ?? '-';
        $body =
            [
                "resourceType" => "Bundle",
                "type" => "transaction",
                "entry" => [
                    // ENCOUNTER
                    [
                        "fullUrl" => "urn:uuid:$encounter",
                        "resource" => [
                            "resourceType" => "Encounter",
                            "status" => "finished",
                            "class" => [
                                "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                                "code" => "AMB",
                                "display" => "ambulatory"
                            ],
                            "subject" => [
                                "reference" => "Patient/$request->pasien_uuid",
                                "display" => $request->nama
                            ],
                            "participant" => [
                                [
                                    "type" => [
                                        [
                                            "coding" => [
                                                [
                                                    "system" => "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                                    "code" => "ATND",
                                                    "display" => "attender"
                                                ]
                                            ]
                                        ]
                                    ],
                                    "individual" => [
                                        "reference" => "Practitioner/$practitioner",
                                        "display" => $request->datasimpeg['nama']
                                    ]
                                ]
                            ],
                            "period" => [
                                "start" => $antri,
                                "end" => $end
                            ],
                            "location" => [
                                [
                                    "location" => [
                                        "reference" => "Location/" . $ruangId,
                                        "display" => "Ruang " . $ruang. " " . $relmasterRuang['panggil_antrian'] . ", RSUD Mohamad Saleh, Lantai " . $lantai . ", Gedung " . $gedung
                                        // "display" => $request['relmpoli']['ruang']['gedung']
                                    ]
                                ]
                            ],
                            "diagnosis" => $diagnosa,
                            "statusHistory" => [
                                [
                                    "status" => "arrived",
                                    "period" => [
                                        "start" => $antri,
                                        "end" => $start
                                    ]
                                ],
                                [
                                    "status" => "in-progress",
                                    "period" => [
                                        "start" => $start,
                                        "end" => $end
                                    ]
                                ],
                                [
                                    "status" => "finished",
                                    "period" => [
                                        "start" => $end,
                                        "end" => $end
                                    ]
                                ]
                            ],
                            "serviceProvider" => [
                                // "reference" => "Organization/$organization_id"
                                "reference" => "Organization/$organization_id"
                            ],

                            // gak yakin
                            "identifier" => [
                                [
                                    "system" => "http://sys-ids.kemkes.go.id/encounter/$organization_id",
                                    "value" => $request->pasien_uuid
                                    // "value" => "P20240001"
                                ]
                            ]
                        ],
                        "request" => [
                            "method" => "POST",
                            "url" => "Encounter"
                        ]
                    ],
                ]
            ];



        //  CONDITION
        foreach ($request->diagnosa as $key => $value) {
            $cond =
                [
                    // "fullUrl" => "urn:uuid:ba5a7dec-023f-45e1-adb9-1b9d71737a5f",
                    "fullUrl" => $diagnosa[$key]['condition']['reference'],
                    "resource" => [
                        "resourceType" => "Condition",
                        "clinicalStatus" => [
                            "coding" => [
                                [
                                    "system" => "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                    "code" => "active",
                                    "display" => "Active"
                                ]
                            ]
                        ],
                        "category" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://terminology.hl7.org/CodeSystem/condition-category",
                                        "code" => "encounter-diagnosis",
                                        "display" => "Encounter Diagnosis"
                                    ]
                                ]
                            ]
                        ],
                        "code" => [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => $value['rs3'],
                                    "display" => $value['masterdiagnosa']['rs4']
                                ]
                            ]
                        ],
                        "subject" => [
                            "reference" => "Patient/$request->pasien_uuid",
                            "display" => $request->nama
                        ],
                        "encounter" => [
                            "reference" => "urn:uuid:$encounter",
                            "display" => "Kunjungan $request->nama di hari $tgl_kunjungan"
                        ]
                    ],
                    "request" => [
                        "method" => "POST",
                        "url" => "Condition"
                    ]
                ];

            array_push($body['entry'], $cond);
        }


        $observation = self::observation($request, $encounter, $tgl_kunjungan);

        // return $body;


        $send['message'] = 'success';
        $send['data'] = $body;

        return $send;

        
    }

    static function observation($request, $encounter, $tgl_kunjungan)
    {

      $practitioner_uuid = $request->datasimpeg ? $request->datasimpeg['satset_uuid']: '-';
      $nama_practitioner = $request->datasimpeg ? $request->datasimpeg['nama']: '-';
      $uuid = self::generateUuid();
       $form = [
        // "fullUrl" => "urn:uuid:{{Observation_Nadi}}",
        "fullUrl" => "urn:uuid:$uuid",
        "resource" => [
            "resourceType" => "Observation",
            "status" => "final",
            "category" => [
                [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                            "code" => "vital-signs",
                            "display" => "Vital Signs",
                        ],
                    ],
                ],
            ],
            "code" => [
                "coding" => [
                    [
                        "system" => "http://loinc.org",
                        "code" => "8867-4",
                        "display" => "Heart rate",
                    ],
                ],
            ],
            "subject" => [
                "reference" => "Patient/$request->pasien_uuid",
                "display" => $request->nama,
            ],
            "encounter" => ["reference" => "urn:uuid:$encounter"],
            "effectiveDateTime" => $tgl_kunjungan . "T01:10:00+00:00",
            "issued" => $tgl_kunjungan . "T01:10:00+00:00",
            "performer" => [
                [
                    "reference" => "Practitioner/$practitioner_uuid",
                    "display" => $nama_practitioner,
                ],
            ],
            "valueQuantity" => [
                "value" => 80,
                "unit" => "{beats}/min",
                "system" => "http://unitsofmeasure.org",
                "code" => "{beats}/min",
            ],
        ],
        "request" => ["method" => "POST", "url" => "Observation"],
      ];
    }


    public function ygHarusDikerjakan()
    {
      $arrayVar = [
        "resourceType" => "Bundle",
        "type" => "transaction",
        "entry" => [

          // 1. Encounter dikerjakan
            [
                "fullUrl" => "urn:uuid:{{Encounter_id}}",
                "resource" => [
                    "resourceType" => "Encounter",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/encounter/{{Org_ID}}",
                            "value" => "",
                        ],
                    ],
                    "status" => "finished",
                    "statusHistory" => [
                        [
                            "status" => "arrived",
                            "period" => [
                                "start" => "2023-08-31T00:00:00+00:00",
                                "end" => "2023-08-31T01:00:00+00:00",
                            ],
                        ],
                        [
                            "status" => "in-progress",
                            "period" => [
                                "start" => "2023-08-31T01:00:00+00:00",
                                "end" => "2023-08-31T04:05:00+00:00",
                            ],
                        ],
                        [
                            "status" => "finished",
                            "period" => [
                                "start" => "2023-08-31T04:05:00+00:00",
                                "end" => "2023-08-31T04:10:00+00:00",
                            ],
                        ],
                    ],
                    "class" => [
                        "system" =>
                            "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                        "code" => "AMB",
                        "display" => "ambulatory",
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "participant" => [
                        [
                            "type" => [
                                [
                                    "coding" => [
                                        [
                                            "system" =>
                                                "http://terminology.hl7.org/CodeSystem/v3-ParticipationType",
                                            "code" => "ATND",
                                            "display" => "attender",
                                        ],
                                    ],
                                ],
                            ],
                            "individual" => [
                                "reference" => "Practitioner/{{Practitioner_ID}}",
                                "display" => "",
                            ],
                        ],
                    ],
                    "period" => [
                        "start" => "2023-08-31T00:00:00+00:00",
                        "end" => "2023-08-31T02:00:00+00:00",
                    ],
                    "diagnosis" => [
                        [
                            "condition" => [
                                "reference" =>
                                    "urn:uuid:{{Condition_DiagnosisPrimer}}",
                                "display" => "{{DiagnosisPrimer_Text}}",
                            ],
                            "use" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                        "code" => "DD",
                                        "display" => "Discharge diagnosis",
                                    ],
                                ],
                            ],
                            "rank" => 1,
                        ],
                        [
                            "condition" => [
                                "reference" =>
                                    "urn:uuid:{{Condition_DiagnosisSekunder}}",
                                "display" => "{{DiagnosisSekunder_Text}}",
                            ],
                            "use" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                                        "code" => "DD",
                                        "display" => "Discharge diagnosis",
                                    ],
                                ],
                            ],
                            "rank" => 2,
                        ],
                    ],
                    "hospitalization" => [
                        "dischargeDisposition" => [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/discharge-disposition",
                                    "code" => "oth",
                                    "display" => "other-hcf",
                                ],
                            ],
                            "text" =>
                                "Rujukan ke RSUP Fatmawati dengan nomor rujukan {{No_Rujukan_Pasien}}",
                        ],
                    ],
                    "location" => [
                        [
                            "extension" => [
                                [
                                    "extension" => [
                                        [
                                            "url" => "value",
                                            "valueCodeableConcept" => [
                                                "coding" => [
                                                    [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Outpatient",
                                                        "code" => "reguler",
                                                        "display" =>
                                                            "Kelas Reguler",
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            "url" => "upgradeClassIndicator",
                                            "valueCodeableConcept" => [
                                                "coding" => [
                                                    [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/locationUpgradeClass",
                                                        "code" => "kelas-tetap",
                                                        "display" =>
                                                            "Kelas Tetap Perawatan",
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    "url" =>
                                        "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass",
                                ],
                            ],
                            "location" => [
                                "reference" => "Location/{{Location_Poli_id}}",
                                "display" => "",
                            ],
                            "period" => [
                                "start" => "2023-08-31T00:00:00+00:00",
                                "end" => "2023-08-31T02:00:00+00:00",
                            ],
                        ],
                    ],
                    "serviceProvider" => ["reference" => "Organization/{{Org_ID}}"],
                ],
                "request" => ["method" => "POST", "url" => "Encounter"],
            ],

            // 2. Condition
            [
                "fullUrl" => "urn:uuid:c566d6e2-4da0-4895-9bcb-8051dd16548c",
                "resource" => [
                    "resourceType" => "Condition",
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                "code" => "active",
                                "display" => "Active",
                            ],
                        ],
                    ],
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/condition-category",
                                    "code" => "problem-list-item",
                                    "display" => "Problem List Item",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "16932000",
                                "display" => "Batuk darah",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "onsetDateTime" => "2023-02-02T00:00:00+00:00",
                    "recordedDate" => "2023-08-31T01:00:00+00:00",
                    "recorder" => [
                        "reference" => "Practitioner/{{Practitioner_ID}}",
                        "display" => "",
                    ],
                    "note" => [["text" => "Batuk Berdarah sejak 3bl yll"]],
                ],
                "request" => ["method" => "POST", "url" => "Condition"],
            ],

            // 3. Observation
            [
                "fullUrl" => "urn:uuid:{{Observation_Nadi}}",
                "resource" => [
                    "resourceType" => "Observation",
                    "status" => "final",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                    "code" => "vital-signs",
                                    "display" => "Vital Signs",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "8867-4",
                                "display" => "Heart rate",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2023-08-31T01:10:00+00:00",
                    "issued" => "2023-08-31T01:10:00+00:00",
                    "performer" => [
                        [
                            "reference" => "Practitioner/{{Practitioner_ID}}",
                            "display" => "",
                        ],
                    ],
                    "valueQuantity" => [
                        "value" => 80,
                        "unit" => "{beats}/min",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "{beats}/min",
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Observation"],
            ],

            // 4. Observation
            [
                "fullUrl" => "urn:uuid:{{Observation_Kesadaran}}",
                "resource" => [
                    "resourceType" => "Observation",
                    "status" => "final",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/observation-category",
                                    "code" => "vital-signs",
                                    "display" => "Vital Signs",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "67775-7",
                                "display" => "Level of responsiveness",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2023-08-31T01:10:00+00:00",
                    "issued" => "2023-08-31T01:10:00+00:00",
                    "performer" => [
                        [
                            "reference" => "Practitioner/{{Practitioner_ID}}",
                            "display" => "",
                        ],
                    ],
                    "valueCodeableConcept" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "248234008",
                                "display" => "Mentally alert",
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Observation"],
            ],

            // 2. Careplan
            [
                "fullUrl" => "urn:uuid:{{CarePlan_RencanaRawat}}",
                "resource" => [
                    "resourceType" => "CarePlan",
                    "status" => "active",
                    "intent" => "plan",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "736271009",
                                    "display" => "Outpatient care plan",
                                ],
                            ],
                        ],
                    ],
                    "title" => "Rencana Rawat Pasien",
                    "description" => "Rencana Rawat Pasien",
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "created" => "2023-08-31T01:20:00+00:00",
                    "author" => [
                        "reference" => "Practitioner/{{Practitioner_ID}}",
                        "display" => "",
                    ],
                ],
                "request" => ["method" => "POST", "url" => "CarePlan"],
            ],

            // 3. Careplan dikerjakan
            [
                "fullUrl" => "urn:uuid:{{CarePlan_Instruksi}}",
                "resource" => [
                    "resourceType" => "CarePlan",
                    "status" => "active",
                    "intent" => "plan",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "736271009",
                                    "display" => "Outpatient care plan",
                                ],
                            ],
                        ],
                    ],
                    "title" => "Instruksi Medik dan Keperawatan Pasien",
                    "description" =>
                        "Penanganan TB Pasien dilakukan dengan pemberian pengobatan TB.",
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "created" => "2023-08-31T01:20:00+00:00",
                    "author" => ["reference" => "Practitioner/{{Practitioner_ID}}"],
                ],
                "request" => ["method" => "POST", "url" => "CarePlan"],
            ],

            [
                "fullUrl" => "urn:uuid:{{Procedure_PraRad}}",
                "resource" => [
                    "resourceType" => "Procedure",
                    "status" => "not-done",
                    "category" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "103693007",
                                "display" => "Diagnostic procedure",
                            ],
                        ],
                        "text" => "Prosedur diagnostik",
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "792805006",
                                "display" => "Fasting",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "performedPeriod" => [
                        "start" => "2023-07-04T09:30:00+00:00",
                        "end" => "2023-07-04T09:30:00+00:00",
                    ],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" => "Practitioner/{{Practitioner_ID}}",
                                "display" => "",
                            ],
                        ],
                    ],
                    "note" => [
                        ["text" => "Tidak puasa sebelum pemeriksaan radiologi"],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Procedure"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Observation_PraRad}}",
                "resource" => [
                    "resourceType" => "Observation",
                    "status" => "final",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/observation-category",
                                    "code" => "survey",
                                    "display" => "Survey",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "82810-3",
                                "display" => "Pregnancy status",
                            ],
                        ],
                    ],
                    "subject" => ["reference" => "Patient/{{Patient_ID}}"],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" => "Kunjungan  4 Juli 2023",
                    ],
                    "effectiveDateTime" => "2023-07-04T09:30:00+00:00",
                    "issued" => "2023-07-04T09:30:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_ID}}"],
                    ],
                    "valueCodeableConcept" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "60001007",
                                "display" => "Not pregnant",
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Observation"],
            ],
            [
                "fullUrl" => "urn:uuid:{{AllergyIntolerance_PraRad}}",
                "resource" => [
                    "resourceType" => "AllergyIntolerance",
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/allergy/{{Org_ID}}",
                            "value" => "P20240001",
                        ],
                    ],
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical",
                                "code" => "active",
                                "display" => "Active",
                            ],
                        ],
                    ],
                    "verificationStatus" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/allergyintolerance-verification",
                                "code" => "confirmed",
                                "display" => "Confirmed",
                            ],
                        ],
                    ],
                    "category" => ["medication"],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/kfa",
                                "code" => "91000928",
                                "display" => "Barium Sulfate",
                            ],
                        ],
                        "text" => "Alergi Barium Sulfate",
                    ],
                    "patient" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" => "Kunjungan  4 Juli 2023",
                    ],
                    "recordedDate" => "2023-07-04T09:30:00+00:00",
                    "recorder" => [
                        "reference" => "Practitioner/{{Practitioner_ID}}",
                    ],
                ],
                "request" => ["method" => "POST", "url" => "AllergyIntolerance"],
            ],
            [
                "fullUrl" => "urn:uuid:",
                "resource" => [
                    "resourceType" => "ServiceRequest",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/servicerequest/{{Org_ID}}",
                            "value" => "",
                        ],
                        [
                            "use" => "usual",
                            "type" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v2-0203",
                                        "code" => "ACSN",
                                    ],
                                ],
                            ],
                            "system" =>
                                "http://sys-ids.kemkes.go.id/acsn/{{Org_ID}}",
                            "value" => "",
                        ],
                    ],
                    "status" => "active",
                    "intent" => "original-order",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "363679005",
                                    "display" => "Imaging",
                                ],
                            ],
                        ],
                    ],
                    "priority" => "routine",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "24648-8",
                                "display" => "XR Chest PA upr",
                            ],
                        ],
                        "text" => "Pemeriksaan CXR PA",
                    ],
                    "orderDetail" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://dicom.nema.org/resources/ontology/DCM",
                                    "code" => "DX",
                                ],
                            ],
                            "text" => "Modality Code: DX",
                        ],
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://sys-ids.kemkes.go.id/ae-title",
                                    "display" => "XR0001",
                                ],
                            ],
                        ],
                    ],
                    "subject" => ["reference" => "Patient/{{Patient_ID}}"],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "occurrenceDateTime" => "2023-08-31T02:05:00+00:00",
                    "requester" => [
                        "reference" => "Practitioner/{{Practitioner_ID}}",
                        "display" => "",
                    ],
                    "performer" => [
                        [
                            "reference" => "Practitioner/10012572188",
                            "display" => "Dokter Radiologist",
                        ],
                    ],
                    "reasonCode" => [
                        [
                            "text" =>
                                "Permintaan pemeriksaan CXR PA untuk tuberculosis",
                        ],
                    ],
                    "supportingInfo" => [
                        ["reference" => "urn:uuid:{{Observation_PraRad}}"],
                        ["reference" => "urn:uuid:{{Procedure_PraRad}}"],
                        ["reference" => "urn:uuid:{{AllergyIntolerance_PraRad}}"],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "ServiceRequest"],
            ],
            [
                "fullUrl" => "urn:uuid:",
                "resource" => [
                    "resourceType" => "Observation",
                    "basedOn" => [["reference" => "urn:uuid:"]],
                    "status" => "final",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/observation-category",
                                    "code" => "imaging",
                                    "display" => "Imaging",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "24648-8",
                                "display" => "XR Chest PA upr",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2023-08-31T02:35:00+00:00",
                    "issued" => "2023-08-31T02:35:00+00:00",
                    "performer" => [
                        [
                            "reference" => "Practitioner/{{Practitioner_ID}}",
                            "display" => "Dokter Radiologist",
                        ],
                    ],
                    "valueString" => "Left upper and middle lung zones show reticulonodular opacities.
    The left apical lung zone shows a cavitary lesion( active TB).
    Left apical pleural thickening
    Mild mediastinum widening is noted
    Normal heart size.
    Free costophrenic angles.",
                ],
                "request" => ["method" => "POST", "url" => "Observation"],
            ],
            [
                "fullUrl" => "urn:uuid:{{DiagnosticReport_Rad}}",
                "resource" => [
                    "resourceType" => "DiagnosticReport",
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/diagnostic/{{Org_ID}}/rad",
                            "value" => "52343522",
                        ],
                    ],
                    "basedOn" => [["reference" => "urn:uuid:"]],
                    "status" => "final",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v2-0074",
                                    "code" => "RAD",
                                    "display" => "Radiology",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "24648-8",
                                "display" => "XR Chest PA upr",
                            ],
                        ],
                    ],
                    "subject" => ["reference" => "Patient/{{Patient_ID}}"],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "effectiveDateTime" => "2023-08-31T05:00:00+00:00",
                    "issued" => "2023-08-31T05:00:00+00:00",
                    "performer" => [
                        ["reference" => "Practitioner/{{Practitioner_ID}}"],
                        ["reference" => "Organization/{{Org_ID}}"],
                    ],
                    "result" => [["reference" => "urn:uuid:"]],
                    "imagingStudy" => [
                        [
                            "reference" =>
                                "urn:uuid:354e1828-b094-493a-b393-2c18a28476ea",
                        ],
                    ],
                    "conclusion" => "Active Tuberculosis indicated",
                ],
                "request" => ["method" => "POST", "url" => "DiagnosticReport"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Procedure_Terapetik}}",
                "resource" => [
                    "resourceType" => "Procedure",
                    "status" => "completed",
                    "category" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "277132007",
                                "display" => "Therapeutic procedure",
                            ],
                        ],
                        "text" => "Therapeutic procedure",
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                                "code" => "93.94",
                                "display" =>
                                    "Respiratory medication administered by nebulizer",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" =>
                            "Tindakan Nebulisasi  pada Selasa tanggal 31 Agustus 2023",
                    ],
                    "performedPeriod" => [
                        "start" => "2023-08-31T02:27:00+00:00",
                        "end" => "2023-08-31T02:27:00+00:00",
                    ],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" => "Practitioner/{{Practitioner_ID}}",
                                "display" => "",
                            ],
                        ],
                    ],
                    "reasonCode" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => "A15.0",
                                    "display" =>
                                        "Tuberculosis of lung, confirmed by sputum microscopy with or without culture",
                                ],
                            ],
                        ],
                    ],
                    "bodySite" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "74101002",
                                    "display" => "Both lungs",
                                ],
                            ],
                        ],
                    ],
                    "note" => [
                        ["text" => "Nebulisasi untuk melegakan sesak napas"],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Procedure"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Procedure_Konseling}}",
                "resource" => [
                    "resourceType" => "Procedure",
                    "status" => "completed",
                    "category" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "409063005",
                                "display" => "Counselling",
                            ],
                        ],
                        "text" => "Counselling",
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                                "code" => "94.4",
                                "display" => "Other psychotherapy and counselling",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" =>
                            "Konseling  pada Selasa tanggal 31 Agustus 2023",
                    ],
                    "performedPeriod" => [
                        "start" => "2023-08-31T02:27:00+00:00",
                        "end" => "2023-08-31T02:27:00+00:00",
                    ],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" => "Practitioner/{{Practitioner_ID}}",
                                "display" => "",
                            ],
                        ],
                    ],
                    "reasonCode" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => "A15.0",
                                    "display" =>
                                        "Tuberculosis of lung, confirmed by sputum microscopy with or without culture",
                                ],
                            ],
                        ],
                    ],
                    "note" => [
                        [
                            "text" =>
                                "Konseling keresahan pasien karena diagnosis TB",
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Procedure"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Condition_DiagnosisPrimer}}",
                "resource" => [
                    "resourceType" => "Condition",
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                "code" => "active",
                                "display" => "Active",
                            ],
                        ],
                    ],
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/condition-category",
                                    "code" => "encounter-diagnosis",
                                    "display" => "Encounter Diagnosis",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://hl7.org/fhir/sid/icd-10",
                                "code" => "A15.0",
                                "display" =>
                                    "Tuberculosis of lung, confirmed by sputum microscopy with or without culture",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "onsetDateTime" => "2023-08-31T04:10:00+00:00",
                    "recordedDate" => "2023-08-31T04:10:00+00:00",
                ],
                "request" => ["method" => "POST", "url" => "Condition"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Condition_DiagnosisSekunder}}",
                "resource" => [
                    "resourceType" => "Condition",
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                "code" => "active",
                                "display" => "Active",
                            ],
                        ],
                    ],
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/condition-category",
                                    "code" => "encounter-diagnosis",
                                    "display" => "Encounter Diagnosis",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://hl7.org/fhir/sid/icd-10",
                                "code" => "E11.9",
                                "display" =>
                                    "Type 2 diabetes mellitus, Type 2 diabetes mellitus",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" => "Kunjungan  di hari Kamis, 31 Agustus 2023",
                    ],
                    "onsetDateTime" => "2023-08-31T04:10:00+00:00",
                    "recordedDate" => "2023-08-31T04:10:00+00:00",
                ],
                "request" => ["method" => "POST", "url" => "Condition"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Procedure_Edukasi}}",
                "resource" => [
                    "resourceType" => "Procedure",
                    "status" => "completed",
                    "category" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "409073007",
                                "display" => "Education",
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "61310001",
                                "display" => "Nutrition education",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "performedPeriod" => [
                        "start" => "2023-08-31T03:30:00+00:00",
                        "end" => "2023-08-31T03:40:00+00:00",
                    ],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" => "Practitioner/{{Practitioner_ID}}",
                                "display" => "",
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Procedure"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Medication_forRequest}}",
                "resource" => [
                    "resourceType" => "Medication",
                    "meta" => [
                        "profile" => [
                            "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
                        ],
                    ],
                    "extension" => [
                        [
                            "url" =>
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                            "valueCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                        "code" => "NC",
                                        "display" => "Non-compound",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/medication/{{Org_ID}}",
                            "value" => "123456789",
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/kfa",
                                "code" => "93001019",
                                "display" =>
                                    "Rifampicin 150 mg / Isoniazid 75 mg / Pyrazinamide 400 mg / Ethambutol 275 mg Tablet Salut Selaput (KIMIA FARMA)",
                            ],
                        ],
                    ],
                    "status" => "active",
                    "manufacturer" => ["reference" => "Organization/900001"],
                    "form" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.kemkes.go.id/CodeSystem/medication-form",
                                "code" => "BS023",
                                "display" => "Kaplet Salut Selaput",
                            ],
                        ],
                    ],
                    "ingredient" => [
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000330",
                                        "display" => "Rifampin",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 150,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                    "code" => "TAB",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000328",
                                        "display" => "Isoniazid",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 75,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                    "code" => "TAB",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000329",
                                        "display" => "Pyrazinamide",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 400,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                    "code" => "TAB",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000288",
                                        "display" => "Ethambutol",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 275,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                    "code" => "TAB",
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Medication"],
            ],
            [
                "fullUrl" => "urn:uuid:{{MedicationRequest_id}}",
                "resource" => [
                    "resourceType" => "MedicationRequest",
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription/{{Org_ID}}",
                            "value" => "123456788",
                        ],
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription-item/{{Org_ID}}",
                            "value" => "123456788-1",
                        ],
                    ],
                    "status" => "completed",
                    "intent" => "order",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/medicationrequest-category",
                                    "code" => "outpatient",
                                    "display" => "Outpatient",
                                ],
                            ],
                        ],
                    ],
                    "priority" => "routine",
                    "medicationReference" => [
                        "reference" => "urn:uuid:{{Medication_forRequest}}",
                        "display" => "",
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "authoredOn" => "2023-08-31T03:27:00+00:00",
                    "requester" => [
                        "reference" => "Practitioner/{{Practitioner_ID}}",
                        "display" => "",
                    ],
                    "reasonReference" => [
                        [
                            "reference" => "urn:uuid:{{Condition_DiagnosisPrimer}}",
                            "display" => "{{DiagnosisPrimer_Text}}",
                        ],
                    ],
                    "courseOfTherapyType" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/medicationrequest-course-of-therapy",
                                "code" => "continuous",
                                "display" => "Continuing long term therapy",
                            ],
                        ],
                    ],
                    "dosageInstruction" => [
                        [
                            "sequence" => 1,
                            "additionalInstruction" => [
                                [
                                    "coding" => [
                                        [
                                            "system" => "http://snomed.info/sct",
                                            "code" => "418577003",
                                            "display" =>
                                                "Take at regular intervals. Complete the prescribed course unless otherwise directed",
                                        ],
                                    ],
                                ],
                            ],
                            "patientInstruction" =>
                                "4 tablet perhari, diminum setiap hari tanpa jeda sampai prose pengobatan berakhir",
                            "timing" => [
                                "repeat" => [
                                    "frequency" => 1,
                                    "period" => 1,
                                    "periodUnit" => "d",
                                ],
                            ],
                            "route" => [
                                "coding" => [
                                    [
                                        "system" => "http://www.whocc.no/atc",
                                        "code" => "O",
                                        "display" => "Oral",
                                    ],
                                ],
                            ],
                            "doseAndRate" => [
                                [
                                    "type" => [
                                        "coding" => [
                                            [
                                                "system" =>
                                                    "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                "code" => "ordered",
                                                "display" => "Ordered",
                                            ],
                                        ],
                                    ],
                                    "doseQuantity" => [
                                        "value" => 4,
                                        "unit" => "TAB",
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        "code" => "TAB",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "dispenseRequest" => [
                        "dispenseInterval" => [
                            "value" => 1,
                            "unit" => "days",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "d",
                        ],
                        "validityPeriod" => [
                            "start" => "2023-08-31T03:27:00+00:00",
                            "end" => "2024-07-22T14:27:00+00:00",
                        ],
                        "numberOfRepeatsAllowed" => 0,
                        "quantity" => [
                            "value" => 120,
                            "unit" => "TAB",
                            "system" =>
                                "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                            "code" => "TAB",
                        ],
                        "expectedSupplyDuration" => [
                            "value" => 30,
                            "unit" => "days",
                            "system" => "http://unitsofmeasure.org",
                            "code" => "d",
                        ],
                        "performer" => ["reference" => "Organization/{{Org_ID}}"],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "MedicationRequest"],
            ],
            [
                "fullUrl" => "urn:uuid:{{QuestionnaireResponse_KajianResep}}",
                "resource" => [
                    "resourceType" => "QuestionnaireResponse",
                    "questionnaire" =>
                        "https://fhir.kemkes.go.id/Questionnaire/Q0007",
                    "status" => "completed",
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "authored" => "2023-08-31T03:00:00+00:00",
                    "author" => [
                        "reference" => "Practitioner/10009880728",
                        "display" => "Apoteker A",
                    ],
                    "source" => ["reference" => "Patient/{{Patient_ID}}"],
                    "item" => [
                        [
                            "linkId" => "1",
                            "text" => "Persyaratan Administrasi",
                            "item" => [
                                [
                                    "linkId" => "1.1",
                                    "text" =>
                                        "Apakah nama, umur, jenis kelamin, berat badan dan tinggi badan pasien sudah sesuai?",
                                    "answer" => [
                                        [
                                            "valueCoding" => [
                                                "system" =>
                                                    "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                "code" => "OV000052",
                                                "display" => "Sesuai",
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    "linkId" => "1.2",
                                    "text" =>
                                        "Apakah nama, nomor ijin, alamat dan paraf dokter sudah sesuai?",
                                    "answer" => [
                                        [
                                            "valueCoding" => [
                                                "system" =>
                                                    "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                "code" => "OV000052",
                                                "display" => "Sesuai",
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    "linkId" => "1.3",
                                    "text" => "Apakah tanggal resep sudah sesuai?",
                                    "answer" => [
                                        [
                                            "valueCoding" => [
                                                "system" =>
                                                    "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                "code" => "OV000052",
                                                "display" => "Sesuai",
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    "linkId" => "1.4",
                                    "text" =>
                                        "Apakah ruangan/unit asal resep sudah sesuai?",
                                    "answer" => [
                                        [
                                            "valueCoding" => [
                                                "system" =>
                                                    "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                "code" => "OV000052",
                                                "display" => "Sesuai",
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    "linkId" => "2",
                                    "text" => "Persyaratan Farmasetik",
                                    "item" => [
                                        [
                                            "linkId" => "2.1",
                                            "text" =>
                                                "Apakah nama obat, bentuk dan kekuatan sediaan sudah sesuai?",
                                            "answer" => [
                                                [
                                                    "valueCoding" => [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                        "code" => "OV000052",
                                                        "display" => "Sesuai",
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            "linkId" => "2.2",
                                            "text" =>
                                                "Apakah dosis dan jumlah obat sudah sesuai?",
                                            "answer" => [
                                                [
                                                    "valueCoding" => [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                        "code" => "OV000052",
                                                        "display" => "Sesuai",
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            "linkId" => "2.3",
                                            "text" =>
                                                "Apakah stabilitas obat sudah sesuai?",
                                            "answer" => [
                                                [
                                                    "valueCoding" => [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                        "code" => "OV000052",
                                                        "display" => "Sesuai",
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            "linkId" => "2.4",
                                            "text" =>
                                                "Apakah aturan dan cara penggunaan obat sudah sesuai?",
                                            "answer" => [
                                                [
                                                    "valueCoding" => [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                        "code" => "OV000052",
                                                        "display" => "Sesuai",
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                [
                                    "linkId" => "3",
                                    "text" => "Persyaratan Klinis",
                                    "item" => [
                                        [
                                            "linkId" => "3.1",
                                            "text" =>
                                                "Apakah ketepatan indikasi, dosis, dan waktu penggunaan obat sudah sesuai?",
                                            "answer" => [
                                                [
                                                    "valueCoding" => [
                                                        "system" =>
                                                            "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                                        "code" => "OV000052",
                                                        "display" => "Sesuai",
                                                    ],
                                                ],
                                            ],
                                        ],
                                        [
                                            "linkId" => "3.2",
                                            "text" =>
                                                "Apakah terdapat duplikasi pengobatan?",
                                            "answer" => [["valueBoolean" => false]],
                                        ],
                                        [
                                            "linkId" => "3.3",
                                            "text" =>
                                                "Apakah terdapat alergi dan reaksi obat yang tidak dikehendaki (ROTD)?",
                                            "answer" => [["valueBoolean" => false]],
                                        ],
                                        [
                                            "linkId" => "3.4",
                                            "text" =>
                                                "Apakah terdapat kontraindikasi pengobatan?",
                                            "answer" => [["valueBoolean" => false]],
                                        ],
                                        [
                                            "linkId" => "3.5",
                                            "text" =>
                                                "Apakah terdapat dampak interaksi obat?",
                                            "answer" => [["valueBoolean" => false]],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "QuestionnaireResponse"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Medication_forDispense}}",
                "resource" => [
                    "resourceType" => "Medication",
                    "meta" => [
                        "profile" => [
                            "https://fhir.kemkes.go.id/r4/StructureDefinition/Medication",
                        ],
                    ],
                    "extension" => [
                        [
                            "url" =>
                                "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                            "valueCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                        "code" => "NC",
                                        "display" => "Non-compound",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/medication/{{Org_ID}}",
                            "value" => "123456789",
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/kfa",
                                "code" => "93001019",
                                "display" =>
                                    "Rifampicin 150 mg / Isoniazid 75 mg / Pyrazinamide 400 mg / Ethambutol 275 mg Tablet Salut Selaput (KIMIA FARMA)",
                            ],
                        ],
                    ],
                    "status" => "active",
                    "manufacturer" => ["reference" => "Organization/900001"],
                    "form" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.kemkes.go.id/CodeSystem/medication-form",
                                "code" => "BS023",
                                "display" => "Kaplet Salut Selaput",
                            ],
                        ],
                    ],
                    "ingredient" => [
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000330",
                                        "display" => "Rifampin",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 150,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                    "code" => "TAB",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000328",
                                        "display" => "Isoniazid",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 75,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                    "code" => "TAB",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000329",
                                        "display" => "Pyrazinamide",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 400,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                    "code" => "TAB",
                                ],
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => "91000288",
                                        "display" => "Ethambutol",
                                    ],
                                ],
                            ],
                            "isActive" => true,
                            "strength" => [
                                "numerator" => [
                                    "value" => 275,
                                    "system" => "http://unitsofmeasure.org",
                                    "code" => "mg",
                                ],
                                "denominator" => [
                                    "value" => 1,
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                    "code" => "TAB",
                                ],
                            ],
                        ],
                    ],
                    "batch" => [
                        "lotNumber" => "1625042A",
                        "expirationDate" => "2025-07-22T14:27:00+00:00",
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Medication"],
            ],
            [
                "fullUrl" => "urn:uuid:{{MedicationDispense_id}}",
                "resource" => [
                    "resourceType" => "MedicationDispense",
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription/{{Org_ID}}",
                            "value" => "123456788",
                        ],
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/prescription-item/{{Org_ID}}",
                            "value" => "123456788-1",
                        ],
                    ],
                    "status" => "completed",
                    "category" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category",
                                "code" => "outpatient",
                                "display" => "Outpatient",
                            ],
                        ],
                    ],
                    "medicationReference" => [
                        "reference" => "urn:uuid:{{Medication_forDispense}}",
                        "display" => "",
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "context" => ["reference" => "urn:uuid:{{Encounter_id}}"],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" => "Practitioner/{{Practitioner_ID}}",
                                "display" => "Apoteker Miller",
                            ],
                        ],
                    ],
                    "location" => [
                        "reference" => "Location/{{Location_farmasi_id}}",
                        "display" => "Farmasi",
                    ],
                    "authorizingPrescription" => [
                        ["reference" => "urn:uuid:{{MedicationRequest_id}}"],
                    ],
                    "quantity" => [
                        "value" => 120,
                        "system" =>
                            "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                        "code" => "TAB",
                    ],
                    "daysSupply" => [
                        "value" => 30,
                        "unit" => "Day",
                        "system" => "http://unitsofmeasure.org",
                        "code" => "d",
                    ],
                    "whenPrepared" => "2023-08-31T03:27:00+00:00",
                    "whenHandedOver" => "2023-08-31T03:27:00+00:00",
                    "dosageInstruction" => [
                        [
                            "sequence" => 1,
                            "additionalInstruction" => [
                                [
                                    "coding" => [
                                        [
                                            "system" => "http://snomed.info/sct",
                                            "code" => "418577003",
                                            "display" =>
                                                "Take at regular intervals. Complete the prescribed course unless otherwise directed",
                                        ],
                                    ],
                                ],
                            ],
                            "patientInstruction" =>
                                "4 tablet perhari, diminum setiap hari tanpa jeda sampai prose pengobatan berakhir",
                            "timing" => [
                                "repeat" => [
                                    "frequency" => 1,
                                    "period" => 1,
                                    "periodUnit" => "d",
                                ],
                            ],
                            "doseAndRate" => [
                                [
                                    "type" => [
                                        "coding" => [
                                            [
                                                "system" =>
                                                    "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                "code" => "ordered",
                                                "display" => "Ordered",
                                            ],
                                        ],
                                    ],
                                    "doseQuantity" => [
                                        "value" => 4,
                                        "unit" => "TAB",
                                        "system" =>
                                            "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                        "code" => "TAB",
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "MedicationDispense"],
            ],
            [
                "fullUrl" => "urn:uuid:{{ClinicalImpression_Prognosis}}",
                "resource" => [
                    "resourceType" => "ClinicalImpression",
                    "identifier" => [
                        [
                            "use" => "official",
                            "system" =>
                                "http://sys-ids.kemkes.go.id/clinicalimpression/{{Org_ID}}",
                            "value" => "",
                        ],
                    ],
                    "status" => "completed",
                    "description" => " terdiagnosa TB, dan adanya DM-2",
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" => "Kunjungan  di hari Selasa, 31 Agustus 2023",
                    ],
                    "effectiveDateTime" => "2023-10-31T03:37:31+00:00",
                    "date" => "2023-10-31T03:15:31+00:00",
                    "assessor" => [
                        "reference" => "Practitioner/{{Practitioner_ID}}",
                    ],
                    "problem" => [
                        ["reference" => "urn:uuid:{{Condition_DiagnosisPrimer}}"],
                    ],
                    "investigation" => [
                        [
                            "code" => ["text" => "Pemeriksaan CXR PA"],
                            "item" => [
                                [
                                    "reference" =>
                                        "urn:uuid:{{DiagnosticReport_Rad}}",
                                ],
                                ["reference" => "urn:uuid:"],
                            ],
                        ],
                    ],
                    "summary" =>
                        "Prognosis terhadap Tuberkulosis, disertai adanya riwayat Diabetes Mellitus tipe 2",
                    "finding" => [
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://hl7.org/fhir/sid/icd-10",
                                        "code" => "A15.0",
                                        "display" =>
                                            "Tuberculosis of lung, confirmed by sputum microscopy with or without culture",
                                    ],
                                ],
                            ],
                            "itemReference" => [
                                "reference" =>
                                    "urn:uuid:{{Condition_DiagnosisPrimer}}",
                            ],
                        ],
                        [
                            "itemCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" =>
                                            "http://hl7.org/fhir/sid/icd-10",
                                        "code" => "E44.1",
                                        "display" =>
                                            "Mild protein-calorie malnutrition",
                                    ],
                                ],
                            ],
                            "itemReference" => [
                                "reference" =>
                                    "urn:uuid:{{Condition_DiagnosisSekunder}}",
                            ],
                        ],
                    ],
                    "prognosisCodeableConcept" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "170968001",
                                    "display" => "Prognosis good",
                                ],
                            ],
                        ],
                    ],
                ],
                "request" => ["method" => "POST", "url" => "ClinicalImpression"],
            ],
            [
                "fullUrl" => "urn:uuid:",
                "resource" => [
                    "resourceType" => "ServiceRequest",
                    "identifier" => [
                        [
                            "system" =>
                                "http://sys-ids.kemkes.go.id/servicerequest/{{Org_ID}}",
                            "value" => "000012345",
                        ],
                    ],
                    "status" => "active",
                    "intent" => "original-order",
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://snomed.info/sct",
                                    "code" => "3457005",
                                    "display" => "Patient referral",
                                ],
                            ],
                        ],
                    ],
                    "priority" => "routine",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "737481003",
                                "display" => "Inpatient care management",
                            ],
                        ],
                    ],
                    "subject" => ["reference" => "Patient/{{Patient_ID}}"],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" => "Kunjungan  di hari Kamis, 31 Agustus 2023 ",
                    ],
                    "occurrenceDateTime" => "2023-08-31T04:25:00+00:00",
                    "requester" => [
                        "reference" => "Practitioner/{{Practitioner_ID}}",
                        "display" => "",
                    ],
                    "performer" => [
                        [
                            "reference" => "Practitioner/{{Practitioner_ID}}",
                            "display" => "Fatma",
                        ],
                    ],
                    "locationCode" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v3-RoleCode",
                                    "code" => "HOSP",
                                    "display" => "Hospital",
                                ],
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/v3-RoleCode",
                                    "code" => "AMB",
                                    "display" => "Ambulance",
                                ],
                            ],
                        ],
                    ],
                    "reasonCode" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => "A15.0",
                                    "display" =>
                                        "Tuberculosis of lung, confirmed by sputum microscopy with or without culture",
                                ],
                            ],
                        ],
                    ],
                    "patientInstruction" =>
                        "Rujukan ke Rawat Inap RSUP Fatmawati. Dalam keadaan darurat dapat menghubungi hotline Fasyankes di nomor 14045",
                ],
                "request" => ["method" => "POST", "url" => "ServiceRequest"],
            ],
            [
                "fullUrl" => "urn:uuid:{{Condition_Stabil}}",
                "resource" => [
                    "resourceType" => "Condition",
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" =>
                                    "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                "code" => "active",
                                "display" => "Active",
                            ],
                        ],
                    ],
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" =>
                                        "http://terminology.hl7.org/CodeSystem/condition-category",
                                    "code" => "problem-list-item",
                                    "display" => "Problem List Item",
                                ],
                            ],
                        ],
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "359746009",
                                "display" => "Patient\'s condition stable",
                            ],
                        ],
                    ],
                    "subject" => [
                        "reference" => "Patient/{{Patient_ID}}",
                        "display" => "",
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:{{Encounter_id}}",
                        "display" => "Kunjungan  di hari Kamis, 31 Agustus 2023",
                    ],
                ],
                "request" => ["method" => "POST", "url" => "Condition"],
            ],
        ],
      ];
    }
}
