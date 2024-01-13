<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class PostKunjunganHelper
{
    public static function generateUuid()
    {
        return (string) Str::orderedUuid();
    }

    public static function form($request)
    {

        $encounter = self::generateUuid();

        $practitioner = $request->datasimpeg['satset_uuid'];
        #Bundle #1
        $body =
            [
                "resourceType" => "Bundle",
                "type" => "transaction",
                "entry" => [
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
                                "reference" => "Patient/$request->satset_uuid",
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
                                "start" => "2022-12-18T14:00:00+00:00",
                                "end" => "2022-12-18T16:00:00+00:00"
                            ],
                            "location" => [
                                [
                                    "location" => [
                                        "reference" => "Location/b017aa54-f1df-4ec2-9d84-8823815d7228",
                                        "display" => "Ruang 1A, Poliklinik Bedah Rawat Jalan Terpadu, Lantai 2, Gedung G"
                                    ]
                                ]
                            ],
                            "diagnosis" => [
                                [
                                    "condition" => [
                                        "reference" => "urn:uuid:ba5a7dec-023f-45e1-adb9-1b9d71737a5f",
                                        "display" => "Acute appendicitis, other and unspecified"
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
                                    "rank" => 1
                                ],
                                [
                                    "condition" => [
                                        "reference" => "urn:uuid:470fc62d-9ab1-4c90-8d24-fb245c105c59",
                                        "display" => "Dengue haemorrhagic fever"
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
                                    "rank" => 2
                                ]
                            ],
                            "statusHistory" => [
                                [
                                    "status" => "arrived",
                                    "period" => [
                                        "start" => "2022-12-18T14:00:00+00:00",
                                        "end" => "2022-12-18T15:00:00+00:00"
                                    ]
                                ],
                                [
                                    "status" => "in-progress",
                                    "period" => [
                                        "start" => "2022-12-18T15:00:00+00:00",
                                        "end" => "2022-12-18T16:00:00+00:00"
                                    ]
                                ],
                                [
                                    "status" => "finished",
                                    "period" => [
                                        "start" => "2022-12-18T16:00:00+00:00",
                                        "end" => "2022-12-18T16:00:00+00:00"
                                    ]
                                ]
                            ],
                            "serviceProvider" => [
                                "reference" => "Organization/10000004"
                            ],
                            "identifier" => [
                                [
                                    "system" => "http://sys-ids.kemkes.go.id/encounter/1000004",
                                    "value" => "P20240001"
                                ]
                            ]
                        ],
                        "request" => [
                            "method" => "POST",
                            "url" => "Encounter"
                        ]
                    ],
                    [
                        "fullUrl" => "urn:uuid:ba5a7dec-023f-45e1-adb9-1b9d71737a5f",
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
                                        "code" => "K35.8",
                                        "display" => "Acute appendicitis, other and unspecified"
                                    ]
                                ]
                            ],
                            "subject" => [
                                "reference" => "Patient/100000030009",
                                "display" => "Budi Santoso"
                            ],
                            "encounter" => [
                                "reference" => "urn:uuid:0a26ca28-0ea3-486d-8fa9-6f9edd37e567",
                                "display" => "Kunjungan Budi Santoso di hari Selasa, 18 Desember 2022"
                            ]
                        ],
                        "request" => [
                            "method" => "POST",
                            "url" => "Condition"
                        ]
                    ],
                    [
                        "fullUrl" => "urn:uuid:470fc62d-9ab1-4c90-8d24-fb245c105c59",
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
                                        "code" => "A91",
                                        "display" => "Dengue haemorrhagic fever"
                                    ]
                                ]
                            ],
                            "subject" => [
                                "reference" => "Patient/100000030009",
                                "display" => "Budi Santoso"
                            ],
                            "encounter" => [
                                "reference" => "urn:uuid:0a26ca28-0ea3-486d-8fa9-6f9edd37e567",
                                "display" => "Kunjungan Budi Santoso di hari Selasa, 18 Desember 2022"
                            ]
                        ],
                        "request" => [
                            "method" => "POST",
                            "url" => "Condition"
                        ]
                    ]
                ]
            ];
        return $body;
    }
}
