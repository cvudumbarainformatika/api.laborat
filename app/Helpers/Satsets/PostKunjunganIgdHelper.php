<?php

namespace App\Helpers\Satsets;

use App\Helpers\AuthSatsetHelper;
use App\Helpers\BridgingSatsetHelper;
use App\Models\Pasien;
use App\Models\Satset\SatsetErrorRespon;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Msnomed;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostKunjunganIgdHelper
{
    public static function generateUuid()
    {
        return (string) Str::orderedUuid();
    }

    public static function cekKunjungan()
    {
        $tgl = Carbon::now()->subDays(1)->toDateString();

        $data = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs9',
            'rs17.rs4',
            'rs17.rs8',
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs8 as kodepoli',
            'rs19.rs2 as poli',
            'rs17.rs9 as kodedokter',
            'rs21.rs2 as dokter',
            'rs17.rs14 as kodesistembayar',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            'rs15.rs2 as nama',
            'rs15.rs49 as nik',
            'rs17.rs19 as status',
            'rs15.satset_uuid as pasien_uuid',
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())) AS usiatahun')
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2')
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8')
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9')
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14')
            ->where('rs17.rs8', '=', 'POL014')
            ->where('rs17.rs3', 'LIKE', '%' . $tgl . '%')
            ->where('rs17.rs19', '=', '1')
            ->doesntHave('satset')
            ->doesntHave('satset_error')
            ->with([
                'satset:uuid',
                'satset_error:uuid',
                'datasimpeg:nik,nama,kelamin,kdpegsimrs,kddpjp,satset_uuid',
                'relmpoli' => function ($q) {
                    $q->select('rs1', 'kode_ruang', 'rs7 as nama')->with('ruang:kode,uraian,groupper,satset_uuid,departement_uuid,gedung,lantai,ruang');
                },
                'taskid' => function ($q) {
                    $q->select('noreg', 'taskid', 'waktu', 'created_at')
                        ->orderBy('taskid', 'ASC');
                },
                'anamnesis',
                'pemeriksaanfisik' => function ($a) {
                    $a->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                        ->orderBy('id', 'DESC');
                },
                'tindakan' => function ($t) {
                    $t->select('rs73.rs1', 'rs73.rs2', 'rs73.rs3', 'rs73.rs4', 'rs73.rs8', 'rs73.rs9', 'rs30.rs2 as keterangan', 'rs30.rs1 as kode');
                    $t->leftjoin('rs30', 'rs30.rs1', '=', 'rs73.rs4')
                        ->with([
                            'maapingprocedure' => function ($mp) {
                                $mp->select('prosedur_mapping.kdMaster', 'prosedur_mapping.icd9', 'prosedur_master.prosedur')
                                    ->leftjoin('prosedur_master', 'prosedur_master.kd_prosedur', '=', 'prosedur_mapping.icd9');
                            },
                            'maapingsnowmed:kdMaster,kdSnowmed,display',
                            'petugas:nama,kdpegsimrs,satset_uuid'
                        ])
                        ->groupBy('rs73.rs4')
                        ->orderBy('id', 'DESC');
                },
                'diagnosa' => function ($d) {
                    $d->select('rs1', 'rs3', 'rs4', 'rs7', 'rs8');
                    $d->with('masterdiagnosa');
                },
                'planning' => function ($p) {
                    $p->select('rs1', 'rs2', 'rs3', 'rs4', 'rs5', 'tgl', 'user', 'flag');
                    $p->with([
                        'masterpoli:rs1,rs7,rs6,panggil_antrian,displaykode,kode_ruang',
                        'rekomdpjp',
                        'transrujukan',
                        'listkonsul' => function ($lk) {
                            $lk->select('noreg_lama', 'norm', 'tgl_kunjungan', 'tgl_rencana_konsul', 'kdpoli_asal', 'kdpoli_tujuan', 'kddokter_asal', 'flag', 'rs17.rs9 as kdDokterKonsul', 'rs19.kode_ruang')
                                ->leftJoin('rs17', 'rs17.rs4', '=', 'listkonsulanpoli.noreg_lama')
                                ->leftJoin('rs19', 'rs19.rs1', '=', 'listkonsulanpoli.kdpoli_tujuan')
                                ->with('dokterkonsul:kdpegsimrs,nama,satset_uuid', 'lokasikonsul:kode,uraian,satset_uuid');
                        },
                        'spri:noreg,norm,kodeDokter,tglRencanaKontrol,noSuratKontrol,nama,kelamin,user_id',
                        'spri.petugas:nama,kdpegsimrs,satset_uuid',
                        'ranap:rs1,rs2,rs3,rs4,rs5,rs6,rs7,groups,status,hiddens,groups_nama,jenis',
                        'kontrol' => function ($k) {
                            $k->select('noreg', 'norm', 'kodeDokter as kdDokterKontrol', 'poliKontrol', 'tglRencanaKontrol', 'created_at', 'rs19.kode_ruang')
                                ->leftJoin('rs19', 'rs19.rs6', '=', 'bpjs_surat_kontrol.poliKontrol')
                                ->with('dokterkontrol:kddpjp,nama,satset_uuid', 'lokasikontrol:kode,uraian,satset_uuid');
                        },
                        'operasi',
                    ])->orderBy('id', 'DESC');
                },
                'apotek' => function ($apot) {
                    $apot->whereIn('flag', ['3', '4'])->with([
                        'rincian' => function ($ri) {
                            $ri->select(
                                'resep_keluar_r.id',
                                'resep_keluar_r.kdobat',
                                'resep_keluar_r.noresep',
                                'resep_keluar_r.jumlah',
                                'resep_keluar_r.aturan',
                                'resep_keluar_r.konsumsi',
                                'resep_keluar_r.keterangan',
                                'retur_penjualan_r.jumlah_retur',
                                'signa.jumlah as konsumsi_perhari',
                                DB::raw('
                            CASE
                            WHEN retur_penjualan_r.jumlah_retur IS NOT NULL THEN resep_keluar_r.jumlah - retur_penjualan_r.jumlah_retur
                            ELSE resep_keluar_r.jumlah
                            END as qty
                            ')
                            )
                                ->leftJoin('retur_penjualan_r', function ($jo) {
                                    $jo->on('retur_penjualan_r.kdobat', '=', 'resep_keluar_r.kdobat')
                                        ->on('retur_penjualan_r.noresep', '=', 'resep_keluar_r.noresep');
                                })
                                ->leftJoin('signa', 'signa.signa', '=', 'resep_keluar_r.aturan')
                                ->with([
                                    'mobat.kfa'
                                ]);
                        },
                        'rincianracik' => function ($ri) {
                            $ri->select(
                                'resep_keluar_racikan_r.kdobat',
                                'resep_keluar_racikan_r.noresep',
                                'resep_keluar_racikan_r.jumlah',
                                'resep_keluar_racikan_r.jumlahdibutuhkan as qty',
                                'resep_keluar_racikan_r.tiperacikan',
                                'resep_permintaan_keluar_racikan.dosismaksimum',
                                'resep_permintaan_keluar_racikan.aturan',
                            )
                                ->leftJoin('resep_permintaan_keluar_racikan', function ($jo) {
                                    $jo->on('resep_permintaan_keluar_racikan.kdobat', '=', 'resep_keluar_racikan_r.kdobat')
                                        ->on('resep_permintaan_keluar_racikan.noresep', '=', 'resep_keluar_racikan_r.noresep');
                                })
                                ->with([
                                    'mobat.kfa'
                                ]);
                        },
                        'petugas:id,nik,nama,satset_uuid',
                    ])
                        ->orderBy('id', 'DESC');
                },
                'diet',
                'telaahresep' => function ($t) {
                    $t->with('petugas:id,nama,satset_uuid');
                },
                'laborats' => function ($t) {
                    $t->with([
                        'details' => function ($d) {
                            $d->with([
                                'pemeriksaanlab' => function ($p) {
                                    $p->select(
                                        'rs49.*',
                                        'rs49_spesimen.jenis_spesimen',
                                        'rs49_spesimen.jumlah_spesimen',
                                        'rs49_spesimen.volume_spesimen_klinis',
                                        'rs49_spesimen.cara_pengambilan_spesimen',
                                        'rs49_spesimen.cairan_fiksasi',
                                        'rs49_spesimen.volume_cairan_fiksasi',
                                    )->with('loinclab')
                                        ->leftJoin('rs49_spesimen', 'rs49.rs1', '=', 'rs49_spesimen.rs1')
                                        ->orderBy('id', 'ASC');
                                }
                            ])->orderBy('rs4', 'ASC');
                        }
                    ])
                        ->orderBy('id', 'DESC');
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
            ->orderBy('rs17.rs3', 'ASC')
            ->first();

        if (!$data) {
            return ['message' => 'failed', 'data' => 'Tidak ada antrean IGD yang belum terkirim'];
        }

        return self::kirimKunjunganIgd($data);
    }

    public static function cobaIgd($noreg)
    {
        $data = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs9',
            'rs17.rs4',
            'rs17.rs8',
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs8 as kodepoli',
            'rs19.rs2 as poli',
            'rs17.rs9 as kodedokter',
            'rs21.rs2 as dokter',
            'rs17.rs14 as kodesistembayar',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            'rs15.rs2 as nama',
            'rs15.rs49 as nik',
            'rs17.rs19 as status',
            'rs15.satset_uuid as pasien_uuid',
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())) AS usiatahun')
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2')
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8')
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9')
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14')
            ->where('rs17.rs1', '=', $noreg)
            ->with([
                'satset:uuid',
                'satset_error:uuid',
                'datasimpeg:nik,nama,kelamin,kdpegsimrs,kddpjp,satset_uuid',
                'relmpoli' => function ($q) {
                    $q->select('rs1', 'kode_ruang', 'rs7 as nama')->with('ruang:kode,uraian,groupper,satset_uuid,departement_uuid,gedung,lantai,ruang');
                },
                'taskid' => function ($q) {
                    $q->select('noreg', 'taskid', 'waktu', 'created_at')
                        ->orderBy('taskid', 'ASC');
                },
                'anamnesis',
                'pemeriksaanfisik' => function ($a) {
                    $a->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                        ->orderBy('id', 'DESC');
                },
                'tindakan' => function ($t) {
                    $t->select('rs73.rs1', 'rs73.rs2', 'rs73.rs3', 'rs73.rs4', 'rs73.rs8', 'rs73.rs9', 'rs30.rs2 as keterangan', 'rs30.rs1 as kode');
                    $t->leftjoin('rs30', 'rs30.rs1', '=', 'rs73.rs4')
                        ->with([
                            'maapingprocedure' => function ($mp) {
                                $mp->select('prosedur_mapping.kdMaster', 'prosedur_mapping.icd9', 'prosedur_master.prosedur')
                                    ->leftjoin('prosedur_master', 'prosedur_master.kd_prosedur', '=', 'prosedur_mapping.icd9');
                            },
                            'maapingsnowmed:kdMaster,kdSnowmed,display',
                            'petugas:nama,kdpegsimrs,satset_uuid'
                        ])
                        ->groupBy('rs73.rs4')
                        ->orderBy('id', 'DESC');
                },
                'diagnosa' => function ($d) {
                    $d->select('rs1', 'rs3', 'rs4', 'rs7', 'rs8');
                    $d->with('masterdiagnosa');
                },
                'planning' => function ($p) {
                    $p->select('rs1', 'rs2', 'rs3', 'rs4', 'rs5', 'tgl', 'user', 'flag');
                    $p->with([
                        'masterpoli:rs1,rs7,rs6,panggil_antrian,displaykode,kode_ruang',
                        'rekomdpjp',
                        'transrujukan',
                        'listkonsul' => function ($lk) {
                            $lk->select('noreg_lama', 'norm', 'tgl_kunjungan', 'tgl_rencana_konsul', 'kdpoli_asal', 'kdpoli_tujuan', 'kddokter_asal', 'flag', 'rs17.rs9 as kdDokterKonsul', 'rs19.kode_ruang')
                                ->leftJoin('rs17', 'rs17.rs4', '=', 'listkonsulanpoli.noreg_lama')
                                ->leftJoin('rs19', 'rs19.rs1', '=', 'listkonsulanpoli.kdpoli_tujuan')
                                ->with('dokterkonsul:kdpegsimrs,nama,satset_uuid', 'lokasikonsul:kode,uraian,satset_uuid');
                        },
                        'spri:noreg,norm,kodeDokter,tglRencanaKontrol,noSuratKontrol,nama,kelamin,user_id',
                        'spri.petugas:nama,kdpegsimrs,satset_uuid',
                        'ranap:rs1,rs2,rs3,rs4,rs5,rs6,rs7,groups,status,hiddens,groups_nama,jenis',
                        'kontrol' => function ($k) {
                            $k->select('noreg', 'norm', 'kodeDokter as kdDokterKontrol', 'poliKontrol', 'tglRencanaKontrol', 'created_at', 'rs19.kode_ruang')
                                ->leftJoin('rs19', 'rs19.rs6', '=', 'bpjs_surat_kontrol.poliKontrol')
                                ->with('dokterkontrol:kddpjp,nama,satset_uuid', 'lokasikontrol:kode,uraian,satset_uuid');
                        },
                        'operasi',
                    ])->orderBy('id', 'DESC');
                },
                'apotek' => function ($apot) {
                    $apot->whereIn('flag', ['3', '4'])->with([
                        'rincian' => function ($ri) {
                            $ri->select(
                                'resep_keluar_r.id',
                                'resep_keluar_r.kdobat',
                                'resep_keluar_r.noresep',
                                'resep_keluar_r.jumlah',
                                'resep_keluar_r.aturan',
                                'resep_keluar_r.konsumsi',
                                'resep_keluar_r.keterangan',
                                'retur_penjualan_r.jumlah_retur',
                                'signa.jumlah as konsumsi_perhari',
                                DB::raw('
                            CASE
                            WHEN retur_penjualan_r.jumlah_retur IS NOT NULL THEN resep_keluar_r.jumlah - retur_penjualan_r.jumlah_retur
                            ELSE resep_keluar_r.jumlah
                            END as qty
                            ')
                            )
                                ->leftJoin('retur_penjualan_r', function ($jo) {
                                    $jo->on('retur_penjualan_r.kdobat', '=', 'resep_keluar_r.kdobat')
                                        ->on('retur_penjualan_r.noresep', '=', 'resep_keluar_r.noresep');
                                })
                                ->leftJoin('signa', 'signa.signa', '=', 'resep_keluar_r.aturan')
                                ->with([
                                    'mobat.kfa'
                                ]);
                        },
                        'rincianracik' => function ($ri) {
                            $ri->select(
                                'resep_keluar_racikan_r.kdobat',
                                'resep_keluar_racikan_r.noresep',
                                'resep_keluar_racikan_r.jumlah',
                                'resep_keluar_racikan_r.jumlahdibutuhkan as qty',
                                'resep_keluar_racikan_r.tiperacikan',
                                'resep_permintaan_keluar_racikan.dosismaksimum',
                                'resep_permintaan_keluar_racikan.aturan',
                            )
                                ->leftJoin('resep_permintaan_keluar_racikan', function ($jo) {
                                    $jo->on('resep_permintaan_keluar_racikan.kdobat', '=', 'resep_keluar_racikan_r.kdobat')
                                        ->on('resep_permintaan_keluar_racikan.noresep', '=', 'resep_keluar_racikan_r.noresep');
                                })
                                ->with([
                                    'mobat.kfa'
                                ]);
                        },
                        'petugas:id,nik,nama,satset_uuid',
                    ])
                        ->orderBy('id', 'DESC');
                },
                'diet',
                'telaahresep' => function ($t) {
                    $t->with('petugas:id,nama,satset_uuid');
                },
                'laborats' => function ($t) {
                    $t->with([
                        'details' => function ($d) {
                            $d->with([
                                'pemeriksaanlab' => function ($p) {
                                    $p->select(
                                        'rs49.*',
                                        'rs49_spesimen.jenis_spesimen',
                                        'rs49_spesimen.jumlah_spesimen',
                                        'rs49_spesimen.volume_spesimen_klinis',
                                        'rs49_spesimen.cara_pengambilan_spesimen',
                                        'rs49_spesimen.cairan_fiksasi',
                                        'rs49_spesimen.volume_cairan_fiksasi',
                                    )->with('loinclab')
                                        ->leftJoin('rs49_spesimen', 'rs49.rs1', '=', 'rs49_spesimen.rs1')
                                        ->orderBy('id', 'ASC');
                                }
                            ])->orderBy('rs4', 'ASC');
                        }
                    ])
                        ->orderBy('id', 'DESC');
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
            ->first();

        if (!$data) {
            return ['message' => 'failed', 'data' => "Kunjungan IGD noreg $noreg tidak ditemukan"];
        }

        return self::kirimKunjunganIgd($data);
    }

    public static function getPasienByNikSatset($pasien)
    {
        $nik = $pasien->nik;
        $norm = $pasien->norm;

        $token = AuthSatsetHelper::accessToken();
        $params = '/Patient?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;

        $send = BridgingSatsetHelper::get_data($token, $params);

        $data = Pasien::where([
            ['rs49', $nik],
            ['rs1', $norm],
        ])->first();

        if ($send['message'] === 'success') {
            if ($data) {
                $data->satset_uuid = $send['data']['uuid'];
                $data->save();
            }
        }
        return $send;
    }

    public static function getPractitionerFromSatset($pasien)
    {
        $nik = $pasien->datasimpeg ? $pasien->datasimpeg['nik'] : null;
        if (!$nik) {
            return ['message' => 'failed', 'data' => 'NIK Dokter Kosong'];
        }
        $token = AuthSatsetHelper::accessToken();
        $params = '/Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;

        $send = BridgingSatsetHelper::get_data($token, $params);

        $data = Pegawai::where('nik', $nik)->where('aktif', 'AKTIF')->first();

        if ($send['message'] === 'success') {
            if ($data) {
                $data->satset_uuid = $send['data']['uuid'];
                $data->save();
            }
        }
        return $send;
    }

    public static function kirimKunjunganIgd($data)
    {
        $pasien_uuid = $data->pasien_uuid;
        if (!$pasien_uuid) {
            $getPasienFromSatset = self::getPasienByNikSatset($data);
            $pasien_uuid = $getPasienFromSatset['data']['uuid'] ?? null;
        }

        if (!$pasien_uuid) {
            return ['message' => 'failed', 'data' => 'Pasien Belum Terkoneksi Ke Satu Sehat'];
        }

        $practitioner = $data->datasimpeg ? ($data->datasimpeg['satset_uuid'] ?? null) : null;
        if (!$practitioner) {
            $getPrac = self::getPractitionerFromSatset($data);
            $practitioner = $getPrac['data']['uuid'] ?? null;
        }

        if (!$practitioner) {
            return ['message' => 'failed', 'data' => 'Dokter IGD Belum Terkoneksi Ke Satu Sehat'];
        }

        $send = self::form($data, $pasien_uuid, $practitioner);
        if ($send['message'] === 'success') {
            $token = AuthSatsetHelper::accessToken();
            $send = BridgingSatsetHelper::post_bundle($token, $send['data'], $data->noreg);
        }
        return $send;
    }

    public static function form($request, $pasien_uuid, $practitioner)
    {
        $send = [
            'message' => 'failed',
            'data' => null
        ];

        $organization_id = BridgingSatsetHelper::organization_id();
        $encounter = self::generateUuid();
        $tgl_kunjungan = $request->tgl_kunjungan;
        $specimenSnomeds = Msnomed::whereNotNull('spesimen')->get();

        // 1. Waktu IGD
        $waktu = $request->taskid;
        $antri = count($waktu) > 0 ? Carbon::parse($waktu[0]->waktu)->toIso8601String() : Carbon::parse($tgl_kunjungan)->toIso8601String();
        $start = count($waktu) > 1 ? Carbon::parse($waktu[1]->waktu)->toIso8601String() : Carbon::parse($tgl_kunjungan)->addMinutes(5)->toIso8601String();
        $end = count($waktu) > 4 ? Carbon::parse($waktu[4]->waktu)->toIso8601String() : Carbon::parse($tgl_kunjungan)->addHours(2)->toIso8601String();

        // 2. Ruangan IGD
        $relmasterRuang = $request->relmpoli ? $request->relmpoli['ruang'] : null;
        $ruangId = !$relmasterRuang ? '00000000-0000-0000-0000-000000000000' : ($relmasterRuang['satset_uuid'] ?? '00000000-0000-0000-0000-000000000000');
        $ruang = !$relmasterRuang ? 'IGD' : ($relmasterRuang['ruang'] ?? 'IGD');
        $lantai = !$relmasterRuang ? '1' : ($relmasterRuang['lantai'] ?? '1');
        $gedung = !$relmasterRuang ? 'Utama' : ($relmasterRuang['gedung'] ?? 'Utama');

        // 3. Diagnosis & Condition
        $diagnosa_entries = [];
        $condition_entries = [];
        $conds = $request->diagnosa ?? [];
        foreach ($conds as $key => $d) {
            $cond_uuid = self::generateUuid();
            $diagnosa_entries[] = [
                "condition" => [
                    "reference" => "urn:uuid:$cond_uuid",
                    "display" => $d['masterdiagnosa'] ? ($d['masterdiagnosa']['rs4'] ?? $d['masterdiagnosa']['rs3'] ?? 'Diagnosis') : 'Diagnosis',
                ],
                "use" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                            "code" => $d['rs4'] === 'Primer' ? 'AD' : 'DD',
                            "display" => $d['rs4'] === 'Primer' ? 'Admission diagnosis' : 'Discharge diagnosis',
                        ]
                    ]
                ],
                "rank" => $key + 1
            ];

            $condition_entries[] = [
                "fullUrl" => "urn:uuid:$cond_uuid",
                "resource" => [
                    "resourceType" => "Condition",
                    "clinicalStatus" => [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/condition-clinical",
                                "code" => "active",
                                "display" => "Active",
                            ]
                        ]
                    ],
                    "category" => [
                        [
                            "coding" => [
                                [
                                    "system" => "http://terminology.hl7.org/CodeSystem/condition-category",
                                    "code" => "encounter-diagnosis",
                                    "display" => "Encounter Diagnosis",
                                ]
                            ]
                        ]
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://hl7.org/fhir/sid/icd-10",
                                "code" => $d['rs3'],
                                "display" => $d['masterdiagnosa'] ? ($d['masterdiagnosa']['rs4'] ?? $d['masterdiagnosa']['rs3'] ?? 'Diagnosis') : 'Diagnosis',
                            ]
                        ]
                    ],
                    "subject" => ["reference" => "Patient/$pasien_uuid", "display" => $request->nama],
                    "encounter" => ["reference" => "urn:uuid:$encounter"],
                    "recorder" => ["reference" => "Practitioner/$practitioner"],
                ],
                "request" => ["method" => "POST", "url" => "Condition"],
            ];
        }

        if (empty($diagnosa_entries)) {
            return ['message' => 'failed', 'data' => 'Diagnosa Pasien IGD Belum Terisi'];
        }

        // Sub-resources
        $refference = [];
        $anamnesis = PostKunjunganRajalHelper::anamnesis($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);
        $observation = PostKunjunganRajalHelper::observation($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);
        $carePlan = PostKunjunganRajalHelper::carePlan($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);
        $procedure = PostKunjunganRajalHelper::procedure($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);
        $plann = PostKunjunganRajalHelper::planning($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id, $refference);
        $alergyIntoleran = PostKunjunganRajalHelper::allergyIntoleran($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id);
        $apotek = PostKunjunganRajalHelper::apotek($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id);
        $laborats = PostKunjunganRajalHelper::laborats($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id, $specimenSnomeds);
        $radiologis = PostKunjunganRajalHelper::radiologi($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id);

        $body = [
            "resourceType" => "Bundle",
            "type" => "transaction",
            "entry" => [
                // 1. Encounter (EMER)
                [
                    "fullUrl" => "urn:uuid:$encounter",
                    "resource" => [
                        "resourceType" => "Encounter",
                        "identifier" => [
                            [
                                "system" => "http://sys-ids.kemkes.go.id/encounter/" . $organization_id,
                                "value" => $request->noreg ?? $request->rs1
                            ]
                        ],
                        "status" => "finished",
                        "class" => [
                            "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                            "code" => "EMER",
                            "display" => "emergency"
                        ],
                        "subject" => [
                            "reference" => "Patient/$pasien_uuid",
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
                                    "display" => $request->datasimpeg['nama'] ?? '-'
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
                                    "display" => "Ruang IGD, RSUD Mohamad Saleh, Lantai " . $lantai . ", Gedung " . $gedung
                                ]
                            ]
                        ],
                        "diagnosis" => $diagnosa_entries,
                        "statusHistory" => [
                            [
                                "status" => "arrived",
                                "period" => ["start" => $antri, "end" => $start]
                            ],
                            [
                                "status" => "in-progress",
                                "period" => ["start" => $start, "end" => $end]
                            ],
                            [
                                "status" => "finished",
                                "period" => ["start" => $end, "end" => $end]
                            ]
                        ],
                        "serviceProvider" => ["reference" => "Organization/$organization_id"],
                    ],
                    "request" => ["method" => "POST", "url" => "Encounter"]
                ]
            ]
        ];

        // Push Condition
        foreach ($condition_entries as $cond) {
            $body['entry'][] = $cond;
        }

        // Push Anamnesis
        if (!empty($anamnesis['keluhanUtama'])) {
            $body['entry'][] = $anamnesis['keluhanUtama'];
        }

        // Push Observation (TTV, Kesadaran, Fisik)
        if (!empty($observation)) {
            $observations = collect($observation)->unique('fullUrl')->all();
            foreach ($observations as $obs) {
                if ($obs !== null) $body['entry'][] = $obs;
            }
        }

        // Push CarePlan
        if (!empty($carePlan)) {
            $carePlanUnique = collect($carePlan)->unique('fullUrl')->all();
            foreach ($carePlanUnique as $cp) {
                if ($cp !== null) $body['entry'][] = $cp;
            }
        }

        // Push Procedure (Tindakan)
        if (!empty($procedure)) {
            foreach ($procedure as $proc) {
                if ($proc !== null) $body['entry'][] = $proc;
            }
        }

        // Push Planning (SPRI, Konsul, Kontrol)
        if (!empty($plann['spri'])) $body['entry'][] = $plann['spri'];
        if (!empty($plann['konsul'])) $body['entry'][] = $plann['konsul'];
        if (!empty($plann['kontrol'])) $body['entry'][] = $plann['kontrol'];

        // Push Allergy Intolerance
        if (!empty($alergyIntoleran)) $body['entry'][] = $alergyIntoleran;

        // Push Farmasi (Medication, Request, Dispense)
        if (!empty($apotek['nonracikan'])) {
            foreach ($apotek['nonracikan'] as $item_obat) {
                if (!empty($item_obat['medication'])) $body['entry'][] = $item_obat['medication'];
                if (!empty($item_obat['medication_request'])) $body['entry'][] = $item_obat['medication_request'];
                if (!empty($item_obat['medicationD'])) $body['entry'][] = $item_obat['medicationD'];
                if (!empty($item_obat['medication_dispense'])) $body['entry'][] = $item_obat['medication_dispense'];
            }
        }
        if (!empty($apotek['racikan'])) {
            foreach ($apotek['racikan'] as $item_racik) {
                if (!empty($item_racik['medication'])) $body['entry'][] = $item_racik['medication'];
                if (!empty($item_racik['medication_request'])) $body['entry'][] = $item_racik['medication_request'];
                if (!empty($item_racik['medicationD'])) $body['entry'][] = $item_racik['medicationD'];
                if (!empty($item_racik['medication_dispense'])) $body['entry'][] = $item_racik['medication_dispense'];
            }
        }

        // Push Laborat
        if (!empty($laborats)) {
            foreach ($laborats as $lab) {
                if (!empty($lab['serviceRequests'])) $body['entry'][] = $lab['serviceRequests'];
                if (!empty($lab['hasil'])) $body['entry'][] = $lab['hasil'];
                if (!empty($lab['spesimen'])) $body['entry'][] = $lab['spesimen'];
                if (!empty($lab['diagnosticReport'])) $body['entry'][] = $lab['diagnosticReport'];
            }
        }

        // Push Radiologi
        if (!empty($radiologis)) {
            foreach ($radiologis as $rad) {
                if ($rad !== null) $body['entry'][] = $rad;
            }
        }

        $send['message'] = 'success';
        $send['data'] = $body;

        return $send;
    }
}
