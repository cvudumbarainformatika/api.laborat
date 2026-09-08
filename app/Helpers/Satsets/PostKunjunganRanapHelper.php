<?php

namespace App\Helpers\Satsets;

use App\Helpers\AuthSatsetHelper;
use App\Helpers\BridgingbpjsHelper;
use App\Helpers\BridgingSatsetHelper;
use App\Helpers\Satsets\PostKunjunganRajalHelper;
use App\Models\Pasien;
use App\Models\Satset\SatsetErrorRespon;
use App\Models\Satset\SatsetAuditDataLog;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Msnomed;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostKunjunganRanapHelper
{
    public static function ranap($tgl = null)
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
                'patient' => function ($q) {
                    $q->select(
                        'rs1',
                        'kd_propinsi',
                        'kd_kota',
                        'kd_kec',
                        'kd_kel'
                    )
                        ->addSelect([
                            'kota.wilayah as nama_kota',
                            'kec.wilayah as nama_kecamatan',
                            'kel.wilayah as nama_kelurahan',
                        ])
                        ->leftJoin('wilayah as kota', function ($join) {
                            $join->on('kota.kode2', '=', 'rs15.kd_propinsi')
                                ->on('kota.kode3', '=', 'rs15.kd_kota')
                                ->where('kota.kode4', '')
                                ->where('kota.kode5', '');
                        })
                        ->leftJoin('wilayah as kec', function ($join) {
                            $join->on('kec.kode2', '=', 'rs15.kd_propinsi')
                                ->on('kec.kode3', '=', 'rs15.kd_kota')
                                ->on('kec.kode4', '=', 'rs15.kd_kec')
                                ->where('kec.kode5', '');
                        })
                        ->leftJoin('wilayah as kel', function ($join) {
                            $join->on('kel.kode2', '=', 'rs15.kd_propinsi')
                                ->on('kel.kode3', '=', 'rs15.kd_kota')
                                ->on('kel.kode4', '=', 'rs15.kd_kec')
                                ->on('kel.kode5', '=', 'rs15.kd_kel');
                        })
                        ->selectRaw("kel.kode2 as satset_province")
                        ->selectRaw(" CONCAT( kel.kode2, LPAD(kel.kode3, 2, '0') ) as satset_city ")
                        ->selectRaw(" CONCAT( kel.kode2, LPAD(kel.kode3, 2, '0'), LPAD(kel.kode4, 2, '0') ) as satset_district ")
                        ->selectRaw(" CONCAT( kel.kode2, LPAD(kel.kode3, 2, '0'), LPAD(kel.kode4, 2, '0'), LPAD(kel.kode5, 4, '0') ) as satset_village ");
                },
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
                'nursenote' => function ($t) {
                    $t->select('id', 'noreg', 'user', 'reseps');
                    $t->with('petugas:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto');
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
                }
            ])

            ->whereIn('rs23.rs22', ['2', '3']); // Status sudah pulang

        if ($tgl) {
            $select->where('rs23.rs4', 'LIKE', $tgl . '%');
        } else {
            $tglAwal = Carbon::now()->subDays(60)->toDateString() . ' 00:00:00';
            $tglAkhir = Carbon::now()->subDays(1)->toDateString() . ' 23:59:59';
            $select->whereBetween('rs23.rs4', [$tglAwal, $tglAkhir])
                   ->has('diagnosa');
        }

        $data = $select
            ->doesntHave('satset')
            ->doesntHave('satset_error') // Belum terkirim
            ->orderBy('rs23.rs4', 'asc')
            ->first();

        // return $select;
        return self::kirimKunjunganRanap($data);
    }

    public static function cobaRanap($noreg)
    {
        // 1. Ambil tanggal 5 hari yang lalu
        // $tglTarget = Carbon::now()->subDays(5)->toDateString();

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
            ->where('rs23.rs1', $noreg)
            ->with([
                'patient' => function ($q) {
                    $q->select(
                        'rs1',
                        'kd_propinsi',
                        'kd_kota',
                        'kd_kec',
                        'kd_kel'
                    )
                        ->addSelect([
                            'kota.wilayah as nama_kota',
                            'kec.wilayah as nama_kecamatan',
                            'kel.wilayah as nama_kelurahan',
                        ])
                        ->leftJoin('wilayah as kota', function ($join) {
                            $join->on('kota.kode2', '=', 'rs15.kd_propinsi')
                                ->on('kota.kode3', '=', 'rs15.kd_kota')
                                ->where('kota.kode4', '')
                                ->where('kota.kode5', '');
                        })
                        ->leftJoin('wilayah as kec', function ($join) {
                            $join->on('kec.kode2', '=', 'rs15.kd_propinsi')
                                ->on('kec.kode3', '=', 'rs15.kd_kota')
                                ->on('kec.kode4', '=', 'rs15.kd_kec')
                                ->where('kec.kode5', '');
                        })
                        ->leftJoin('wilayah as kel', function ($join) {
                            $join->on('kel.kode2', '=', 'rs15.kd_propinsi')
                                ->on('kel.kode3', '=', 'rs15.kd_kota')
                                ->on('kel.kode4', '=', 'rs15.kd_kec')
                                ->on('kel.kode5', '=', 'rs15.kd_kel');
                        })
                        ->selectRaw("kel.kode2 as satset_province")
                        ->selectRaw(" CONCAT( kel.kode2, LPAD(kel.kode3, 2, '0') ) as satset_city ")
                        ->selectRaw(" CONCAT( kel.kode2, LPAD(kel.kode3, 2, '0'), LPAD(kel.kode4, 2, '0') ) as satset_district ")
                        ->selectRaw(" CONCAT( kel.kode2, LPAD(kel.kode3, 2, '0'), LPAD(kel.kode4, 2, '0'), LPAD(kel.kode5, 4, '0') ) as satset_village ");
                },
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
                'nursenote' => function ($t) {
                    $t->select('id', 'noreg', 'user', 'reseps');
                    $t->with('petugas:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto');
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
                }
            ])

            // ->where('rs23.rs1', $noreg)
            // ->where('rs23.rs4', 'LIKE', $tglTarget . '%')
            ->whereIn('rs23.rs22', ['2', '3'])                                   // Status sudah pulang
            // ->doesntHave('satset')                                               // Bypass agar bisa coba berulang kali
            // ->doesntHave('satset_error')                                         // Belum terkirim
            ->orderBy('rs23.rs4', 'asc')

            ->first();

        // return $select;
        return self::kirimKunjunganRanap($select);
    }

    public static function kirimKunjunganRanap($data)
    {
        // dd($data);
        if (!$data) {
            return ['message' => 'error', 'data' => 'Data Kunjungan Rawat Inap tidak ditemukan atau sudah pernah dikirim.'];
        }
        $pasien_uuid =  $data?->pasien_uuid ?? null;
        $practitioner_uuid = $data?->datasimpeg?->satset_uuid;

        // dd($pasien_uuid);

        if (!$pasien_uuid) {
            // $getPasienFromSatset = self::getPasienByNikSatset($data);
            // $pasien_uuid = $getPasienFromSatset['data']['uuid'] ?? null;
            $result = self::getPasienByNikSatset($data);


            // =====================================
            // PASIEN SUDAH ADA DI SATUSEHAT
            // =====================================
            if ($result['message'] === 'success') {
                $pasien_uuid = $result['uuid'];
            }

            // =====================================
            // PASIEN BELUM ADA
            // =====================================
            if ($result['message'] === 'not-found') {

                // =====================================
                // CREATE PATIENT KE SATUSEHAT
                // =====================================
                $create = self::createPatientSatset($data);
                // dd(json_encode($create, JSON_PRETTY_PRINT));
                // berhasil create
                if (($create['message'] ?? null) === 'success') {
                    $pasien_uuid = $create['uuid'];
                }
            }

            // dd(json_encode($result, JSON_PRETTY_PRINT));
        }

        if (!$practitioner_uuid) {
            $getFromSatset = self::getPractitionerFromSatset($data);
            $practitioner_uuid = $getFromSatset['data']['uuid'] ?? null;
        }

        if (!$practitioner_uuid) {
            $err = [
                'method' => 'POST',
                'url' => 'https://api-satusehat.kemkes.go.id/fhir-r4/v1',
                'response' => ['message' => 'Practitioner UUID Dokter Tidak Ditemukan di SatuSehat'],
                'uuid' => $data->noreg,
                'jenis' => 'ranap',
                'error_summary' => 'Practitioner Dokter Tidak Ditemukan',
            ];
            SatsetErrorRespon::create($err);
            return ['message' => 'failed', 'data' => 'Practitioner UUID Dokter Tidak Ditemukan'];
        }

        if (!$pasien_uuid) {
            $err = [
                'method' => 'POST',
                'url' => 'https://api-satusehat.kemkes.go.id/fhir-r4/v1',
                'response' => ['message' => 'Pasien UUID / NIK Tidak Ditemukan di SatuSehat'],
                'uuid' => $data->noreg,
                'jenis' => 'ranap',
                'error_summary' => 'Pasien UUID / NIK Tidak Ditemukan',
            ];
            SatsetErrorRespon::create($err);
            return ['message' => 'failed', 'data' => 'Pasien UUID / NIK Tidak Ditemukan'];
        }

        $send = self::form($data, $pasien_uuid);
        if ($send['message'] === 'success') {
            $token = AuthSatsetHelper::accessToken();
            $send = BridgingSatsetHelper::post_bundle($token, $send['data'], $data->noreg, 'ranap');
        }
        return $send;
    }



    public static function fetchBpjsPeserta($pasien, $unit = 'ranap')
    {
        $nik = trim((string)($pasien->nik ?? $pasien->rs49 ?? ''));
        $noka = trim((string)($pasien->noka ?? $pasien->rs46 ?? ''));
        $tglSep = date('Y-m-d');
        $bpjsPeserta = null;

        // 1. Coba by NIK jika valid 16 digit dan bukan dummy
        if (!empty($nik) && strlen($nik) === 16 && !str_starts_with($nik, '8888') && !str_starts_with($nik, '9999') && !str_starts_with($nik, '0000')) {
            try {
                $res = BridgingbpjsHelper::get_url('vclaim', 'Peserta/nik/' . $nik . '/tglSEP/' . $tglSep);
                if (isset($res['result']->peserta) && !empty($res['result']->peserta->nik)) {
                    $bpjsPeserta = $res['result']->peserta;
                }
            } catch (\Throwable $e) {}
        }

        // 2. Coba by NOKA jika belum dapat atau NIK dummy/kosong
        if (!$bpjsPeserta && !empty($noka) && strlen($noka) >= 10) {
            try {
                $res = BridgingbpjsHelper::get_url('vclaim', 'Peserta/nokartu/' . $noka . '/tglSEP/' . $tglSep);
                if (isset($res['result']->peserta) && !empty($res['result']->peserta->nik)) {
                    $bpjsPeserta = $res['result']->peserta;
                }
            } catch (\Throwable $e) {}
        }

        // Audit Log jika terdeteksi ketidaksesuaian data input SIMRS vs BPJS
        if ($bpjsPeserta) {
            $norm = trim((string)($pasien->norm ?? $pasien->rs1 ?? ''));
            $noreg = $pasien->noreg ?? ($pasien->rs1 ?? null);
            $namaSimrs = trim((string)(!empty($pasien->nama) ? $pasien->nama : (!empty($pasien->rs2) ? $pasien->rs2 : '')));
            $tglLahirSimrs = $pasien->tgllahir ?? $pasien->rs16 ?? null;

            $nikBpjs = trim((string)$bpjsPeserta->nik);
            $namaBpjs = trim((string)$bpjsPeserta->nama);
            $tglLahirBpjs = trim((string)$bpjsPeserta->tglLahir);

            $diffNik = empty($nik) || $nik !== $nikBpjs || str_starts_with($nik, '8888') || str_starts_with($nik, '9999') || strlen($nik) < 16;
            $diffNama = !empty($namaSimrs) && strtolower($namaSimrs) !== strtolower($namaBpjs);
            $diffTgl = !empty($tglLahirSimrs) && trim((string)$tglLahirSimrs) !== $tglLahirBpjs;

            if ($diffNik || $diffNama || $diffTgl) {
                $alasan = [];
                if ($diffNik) $alasan[] = "NIK SIMRS ('" . ($nik ?: 'KOSONG') . "') berbeda dg BPJS ('" . $nikBpjs . "')";
                if ($diffNama) $alasan[] = "Nama SIMRS ('$namaSimrs') berbeda dg BPJS ('$namaBpjs')";
                if ($diffTgl) $alasan[] = "Tgl Lahir SIMRS ('$tglLahirSimrs') berbeda dg BPJS ('$tglLahirBpjs')";

                SatsetAuditDataLog::recordAudit([
                    'kategori' => 'PASIEN_MISMATCH_BPJS',
                    'unit' => $unit,
                    'ref_id' => $norm ?: ($noreg ?: '-'),
                    'noreg' => $noreg,
                    'nama' => $namaSimrs ?: $namaBpjs,
                    'nik_simrs' => $nik ?: null,
                    'nik_valid' => $nikBpjs,
                    'data_simrs' => [
                        'nama' => $namaSimrs,
                        'nik' => $nik,
                        'tgllahir' => $tglLahirSimrs,
                        'noka' => $noka,
                    ],
                    'data_pembanding' => [
                        'nama' => $namaBpjs,
                        'nik' => $nikBpjs,
                        'tglLahir' => $tglLahirBpjs,
                        'noKartu' => $bpjsPeserta->noKartu ?? null,
                        'sex' => $bpjsPeserta->sex ?? null,
                    ],
                    'keterangan' => implode('; ', $alasan),
                ]);
            }
        }

        return $bpjsPeserta;
    }

    public static function createPatientSatset($pasien)
    {
        try {
            $token = AuthSatsetHelper::accessToken();

            // 1. Prioritaskan ambil data valid dari BPJS (terintegrasi Dukcapil)
            $bpjs = self::fetchBpjsPeserta($pasien);

            $nik = $bpjs ? trim((string)$bpjs->nik) : trim((string)($pasien->nik ?? $pasien->rs49 ?? ''));
            $norm = trim((string)($pasien->norm ?? $pasien->rs1 ?? ''));

            if (empty($nik) || strlen($nik) < 16) {
                return [
                    'message' => 'failed',
                    'data' => 'NIK Pasien kosong atau kurang dari 16 digit (baik di SIMRS maupun BPJS)'
                ];
            }

            $tgllahir = $bpjs ? trim((string)$bpjs->tglLahir) : ($pasien->tgllahir ?? $pasien->rs16 ?? null);

            $isBayi = false;
            if (!empty($tgllahir)) {
                $isBayi = Carbon::parse($tgllahir)->diffInYears(now()) < 1;
            }

            $genderRaw = $bpjs ? trim((string)$bpjs->sex) : trim((string)($pasien->kelamin ?? $pasien->rs17 ?? ''));
            $genderLower = strtolower($genderRaw);
            $gender = ($genderLower === 'l' || str_starts_with($genderLower, 'laki') || $genderLower === 'male') ? 'male' : 'female';

            $nama = $bpjs ? trim((string)$bpjs->nama) : (!empty($pasien->nama) ? $pasien->nama : (!empty($pasien->rs2) ? $pasien->rs2 : ($pasien->nama_panggil ?? '-')));
            $alamat = $pasien->alamatbarcode ?? $pasien->alamat ?? $pasien->rs4 ?? '-';
            $templahir = $pasien->templahir ?? $pasien->rs37 ?? '-';
            $nohp = ($bpjs && !empty($bpjs->mr->noTelepon)) ? trim((string)$bpjs->mr->noTelepon) : ($pasien->nohp ?? $pasien->rs55 ?? '-');


            $rawProv = trim((string)($pasien?->patient?->satset_province ?? $pasien?->satset_province ?? $pasien?->kd_propinsi ?? '35'));
            $prov = (strlen($rawProv) === 2 && $rawProv !== '00') ? $rawProv : '35';

            $rawCity = trim((string)($pasien?->patient?->satset_city ?? $pasien?->satset_city ?? $pasien?->kd_kota ?? '74'));
            $cityCode = (strlen($rawCity) >= 4) ? $rawCity : ((strlen($rawCity) > 0 && $rawCity !== '00') ? ($prov . str_pad($rawCity, 2, '0', STR_PAD_LEFT)) : ($prov === '35' ? '3574' : $prov . '01'));

            $rawDist = trim((string)($pasien?->patient?->satset_district ?? $pasien?->satset_district ?? $pasien?->kd_kec ?? '02'));
            $distCode = (strlen($rawDist) >= 6) ? $rawDist : ((strlen($rawDist) > 0 && $rawDist !== '00') ? ($cityCode . str_pad($rawDist, 2, '0', STR_PAD_LEFT)) : ($cityCode === '3574' ? '357402' : $cityCode . '01'));

            $rawVill = trim((string)($pasien?->patient?->satset_village ?? $pasien?->satset_village ?? $pasien?->kd_kel ?? '1005'));
            $villCode = (strlen($rawVill) >= 10) ? $rawVill : ((strlen($rawVill) > 0 && $rawVill !== '0000' && $rawVill !== '00') ? ($distCode . str_pad($rawVill, 4, '0', STR_PAD_LEFT)) : ($distCode === '357402' ? '3574021005' : $distCode . '1001'));

            $payload = [
                "resourceType" => "Patient",
                "meta" => [
                    "profile" => [
                        "https://fhir.kemkes.go.id/r4/StructureDefinition/Patient"
                    ]
                ],
                "active" => true,
                "identifier" => [
                    [
                        "use" => "official",
                        "system" => "https://fhir.kemkes.go.id/id/nik",
                        "value" => $nik
                    ]
                ],
                "name" => [
                    [
                        "use" => "official",
                        "text" => $nama
                    ]
                ],
                "gender" => $gender,
                "birthDate" => $tgllahir,
                "deceasedBoolean" => false,
                "multipleBirthBoolean" => false,

                // Telecom (opsional tapi membantu)
                "telecom" => [
                    [
                        "system" => "phone",
                        "value" => $nohp ?: '-',
                        "use" => "mobile"
                    ]
                ],

                "address" => [
                    [
                        "use" => "home",
                        "line" => [(string)$alamat],
                        "city" => !empty($pasien?->patient?->nama_kota) && $pasien?->patient?->nama_kota !== '-' ? $pasien->patient->nama_kota : "KOTA PROBOLINGGO",
                        "district" => !empty($pasien?->patient?->nama_kecamatan) && $pasien?->patient?->nama_kecamatan !== '-' ? $pasien->patient->nama_kecamatan : "Wonoasih",
                        "country" => "ID",
                        "extension" => [
                            [
                                "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeCode",
                                "extension" => [
                                    ["url" => "province", "valueCode" => (string)$prov],
                                    ["url" => "city",    "valueCode" => (string)$cityCode],
                                    ["url" => "district", "valueCode" => (string)$distCode],
                                    ["url" => "village", "valueCode" => (string)$villCode]
                                ]
                            ]
                        ]
                    ]
                ],

                // Extension
                "extension" => [
                    [
                        "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/birthPlace",
                        "valueAddress" => [
                            "city" => $templahir ?: "PROBOLINGGO",
                            "country" => "ID"
                        ]
                    ],
                    [
                        "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/citizenshipStatus",
                        "valueCode" => "WNI"
                    ]
                ],

                "communication" => [
                    [
                        "language" => [
                            "coding" => [
                                [
                                    "system" => "urn:ietf:bcp:47",
                                    "code" => "id-ID",
                                    "display" => "Indonesian"
                                ]
                            ],
                            "text" => "Indonesian"
                        ],
                        "preferred" => true
                    ]
                ]
            ];

            // ==================== LOGIKA BAYI ====================
            if ($isBayi) {
                // Marital Status untuk Bayi
                $payload['maritalStatus'] = [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/v3-MaritalStatus",
                            "code" => "S",
                            "display" => "Never Married"
                        ]
                    ]
                ];
            }

            // dd(json_encode($payload, JSON_PRETTY_PRINT)); // aktifkan jika debugging

            $send = BridgingSatsetHelper::post_data($token, '/Patient', $payload);

            if (isset($send['data']['id']) && !empty($send['data']['id'])) {
                $uuid = $send['data']['id'];

                if (!empty($norm)) {
                    Pasien::where('rs1', $norm)->update(['satset_uuid' => $uuid]);
                } elseif (!empty($nik)) {
                    Pasien::where('rs49', $nik)->update(['satset_uuid' => $uuid]);
                }

                return [
                    'message' => 'success',
                    'uuid' => $uuid,
                    'is_bayi' => $isBayi
                ];
            }

            // Gagal
            SatsetErrorRespon::create([
                'uuid' => $pasien->noreg,
                'response' => $send,
                'jenis' => 'ranap',
                'error_summary' => 'Gagal verifikasi / create Patient IHS SatuSehat'
            ]);

            return [
                'message' => 'failed',
                'data' => $send
            ];
        } catch (\Throwable $e) {
            SatsetErrorRespon::create([
                'uuid' => $pasien->noreg,
                'response' => $e->getMessage(),
                'jenis' => 'ranap',
                'error_summary' => 'Exception createPatientSatset: ' . substr($e->getMessage(), 0, 200)
            ]);

            return [
                'message' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    public static function updateNikPasien($noreg)
    {
        try {
            // 1. Ambil data kunjungan dan data pasien lengkap beserta relasi wilayah
            $query = Kunjunganranap::query();
            $pasien = $query->select(
                'rs23.rs1 as noreg',
                'rs23.rs2 as norm',
                'rs15.rs2 as nama_panggil',
                'rs15.rs4 as alamatbarcode',
                'rs15.rs16 as tgllahir',
                'rs15.rs17 as kelamin',
                'rs15.rs37 as templahir',
                'rs15.rs49 as nik',
                'rs15.rs55 as nohp',
                'rs15.satset_uuid as pasien_uuid'
            )
            ->leftjoin('rs15', 'rs15.rs1', 'rs23.rs2')
            ->where('rs23.rs1', $noreg)
            ->with([
                'patient' => function ($q) {
                    $q->select(
                        'rs1',
                        'kd_propinsi',
                        'kd_kota',
                        'kd_kec',
                        'kd_kel'
                    )
                        ->addSelect([
                            'kota.wilayah as nama_kota',
                            'kec.wilayah as nama_kecamatan',
                            'kel.wilayah as nama_kelurahan',
                        ])
                        ->leftJoin('wilayah as kota', function ($join) {
                            $join->on('kota.kode2', '=', 'rs15.kd_propinsi')
                                ->on('kota.kode3', '=', 'rs15.kd_kota')
                                ->where('kota.kode4', '')
                                ->where('kota.kode5', '');
                        })
                        ->leftJoin('wilayah as kec', function ($join) {
                            $join->on('kec.kode2', '=', 'rs15.kd_propinsi')
                                ->on('kec.kode3', '=', 'rs15.kd_kota')
                                ->on('kec.kode4', '=', 'rs15.kd_kec')
                                ->where('kec.kode5', '');
                        })
                        ->leftJoin('wilayah as kel', function ($join) {
                            $join->on('kel.kode2', '=', 'rs15.kd_propinsi')
                                ->on('kel.kode3', '=', 'rs15.kd_kota')
                                ->on('kel.kode4', '=', 'rs15.kd_kec')
                                ->on('kel.kode5', '=', 'rs15.kd_kel');
                        })
                        ->selectRaw("kel.kode2 as satset_province")
                        ->selectRaw(" CONCAT( kel.kode2, LPAD(kel.kode3, 2, '0') ) as satset_city ")
                        ->selectRaw(" CONCAT( kel.kode2, LPAD(kel.kode3, 2, '0'), LPAD(kel.kode4, 2, '0') ) as satset_district ")
                        ->selectRaw(" CONCAT( kel.kode2, LPAD(kel.kode3, 2, '0'), LPAD(kel.kode4, 2, '0'), LPAD(kel.kode5, 4, '0') ) as satset_village ");
                }
            ])
            ->first();

            if (!$pasien) {
                return ['message' => 'error', 'data' => 'Data kunjungan atau pasien tidak ditemukan.'];
            }

            $newNik = trim($pasien->nik);
            if (!$newNik) {
                return ['message' => 'error', 'data' => 'NIK Baru kosong di database lokal.'];
            }

            $token = AuthSatsetHelper::accessToken();

            // 2. Buat payload Patient FHIR lengkap untuk mendaftarkan/menghubungkan NIK baru
            $genderLower = strtolower(trim($pasien->kelamin));
            $gender = ($genderLower === 'l' || str_starts_with($genderLower, 'laki') || $genderLower === 'male') ? 'male' : 'female';

            $payload = [
                "resourceType" => "Patient",
                "meta" => [
                    "profile" => [
                        "https://fhir.kemkes.go.id/r4/StructureDefinition/Patient"
                    ]
                ],
                "active" => true,
                "identifier" => [
                    [
                        "use" => "official",
                        "system" => "https://fhir.kemkes.go.id/id/nik",
                        "value" => $newNik
                    ]
                ],
                "name" => [
                    [
                        "use" => "official",
                        "text" => $pasien->nama_panggil ?? "-",
                    ]
                ],
                "gender" => $gender,
                "birthDate" => $pasien->tgllahir ?? null,
                "deceasedBoolean" => false,
                "multipleBirthBoolean" => false,
                "telecom" => [
                    [
                        "system" => "phone",
                        "value" => $pasien->nohp ?? '-',
                        "use" => "mobile"
                    ]
                ],
                "address" => [
                    [
                        "use" => "home",
                        "line" => [(string)($pasien->alamatbarcode ?? $pasien->alamat ?? "-")],
                        "city" => !empty($pasien?->patient?->nama_kota) && $pasien?->patient?->nama_kota !== '-' ? $pasien->patient->nama_kota : "KOTA PROBOLINGGO",
                        "district" => !empty($pasien?->patient?->nama_kecamatan) && $pasien?->patient?->nama_kecamatan !== '-' ? $pasien->patient->nama_kecamatan : "Wonoasih",
                        "country" => "ID",
                        "extension" => [
                            [
                                "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeCode",
                                "extension" => [
                                    ["url" => "province", "valueCode" => (string)($pasien?->patient?->satset_province ?? '35')],
                                    ["url" => "city",    "valueCode" => (string)($pasien?->patient?->satset_city ?? '3574')],
                                    ["url" => "district", "valueCode" => (string)($pasien?->patient?->satset_district ?? '357402')],
                                    ["url" => "village", "valueCode" => (string)($pasien?->patient?->satset_village ?? '3574021005')]
                                ]
                            ]
                        ]
                    ]
                ],
                "extension" => [
                    [
                        "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/birthPlace",
                        "valueAddress" => [
                            "city" => $pasien?->templahir ?? "-",
                            "country" => "ID"
                        ]
                    ],
                    [
                        "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/citizenshipStatus",
                        "valueCode" => "WNI"
                    ]
                ],
                "communication" => [
                    [
                        "language" => [
                            "coding" => [
                                [
                                    "system" => "urn:ietf:bcp:47",
                                    "code" => "id-ID",
                                    "display" => "Indonesian"
                                ]
                            ],
                            "text" => "Indonesian"
                        ],
                        "preferred" => true
                    ]
                ]
            ];

            // Cek jika pasien adalah bayi (< 1 tahun)
            $isBayi = Carbon::parse($pasien->tgllahir)->diffInYears(now()) < 1;
            if ($isBayi) {
                $payload['maritalStatus'] = [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/v3-MaritalStatus",
                            "code" => "S",
                            "display" => "Never Married"
                        ]
                    ]
                ];
            }

            // 3. Kirim POST /Patient ke SatuSehat
            $send = BridgingSatsetHelper::post_data($token, '/Patient', $payload);

            // Jika sukses mendaftarkan NIK baru ke SatuSehat
            if (isset($send['data']['id']) && !empty($send['data']['id'])) {
                $uuid = $send['data']['id'];

                Pasien::where('rs1', $pasien->norm)
                    ->update([
                        'satset_uuid' => $uuid
                    ]);

                return [
                    'message' => 'success',
                    'local_noreg' => $noreg,
                    'local_nik' => $newNik,
                    'satset_uuid' => $uuid,
                    'detail' => 'NIK berhasil didaftarkan dan dihubungkan ke SatuSehat.',
                    'satset_response' => $send['data']
                ];
            }

            // Jika gagal karena NIK duplikat/sudah ada di SatuSehat, lakukan pencarian data via GET
            $errMessage = json_encode($send);
            if (str_contains(strtolower($errMessage), 'duplicate') || str_contains($errMessage, '20002')) {
                $getPasien = self::getPasienByNikSatset($pasien);
                if ($getPasien['message'] === 'success') {
                    $uuid = $getPasien['uuid'];
                    return [
                        'message' => 'success',
                        'local_noreg' => $noreg,
                        'local_nik' => $newNik,
                        'satset_uuid' => $uuid,
                        'detail' => 'NIK sudah terdaftar di SatuSehat. Berhasil mencocokkan dan memperbarui database lokal.',
                        'satset_response' => $getPasien
                    ];
                }
            }

            return [
                'message' => 'failed',
                'local_noreg' => $noreg,
                'local_nik' => $newNik,
                'detail' => 'Gagal mendaftarkan NIK ke SatuSehat.',
                'satset_response' => $send
            ];
        } catch (\Throwable $e) {
            return [
                'message' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }



    public static function getPasienByNikSatset($pasien)
    {
        // dd($pasien);
        if (!$pasien) {
            return ['message' => 'failed', 'data' => 'Data pasien kosong'];
        }
        $nik   = trim((string)($pasien->nik ?? $pasien->rs49 ?? ''));
        $norm  = $pasien->norm ?? $pasien->rs1 ?? null;
        $noreg = $pasien->noreg ?? $pasien->rs1 ?? null;

        // Jika NIK belum valid atau kosong, coba cari di BPJS terlebih dahulu
        if (empty($nik) || strlen($nik) !== 16 || str_starts_with($nik, '8888') || str_starts_with($nik, '9999')) {
            $bpjs = self::fetchBpjsPeserta($pasien);
            if ($bpjs && !empty($bpjs->nik)) {
                $nik = trim((string)$bpjs->nik);
            }
        }

        $token  = AuthSatsetHelper::accessToken();
        $params = '/Patient?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;

        $send = BridgingSatsetHelper::get_data($token, $params);

        $data = Pasien::where([
            ['rs49', $nik],
            ['rs1', $norm],
        ])->first();

        // dd(json_encode($send, JSON_PRETTY_PRINT));
        // =========================
        // RESPONSE VALID
        // =========================
        if (
            isset($send['data']['response']['total'])
        ) {

            $total = $send['data']['response']['total'];

            // =========================
            // PASIEN DITEMUKAN
            // =========================
            if ($total > 0) {

                $entry = $send['data']['response']['entry'][0]['resource'] ?? null;

                if ($entry) {

                    $data->satset_uuid = $entry['id'];
                    $data->save();

                    return [
                        'message' => 'success',
                        'exists'  => true,
                        'uuid'    => $entry['id']
                    ];
                }
            }

            // =========================
            // PASIEN TIDAK DITEMUKAN
            // =========================
            return [
                'message' => 'not-found',
                'exists'  => false,
                'uuid'    => null
            ];
        }

        // =========================
        // ERROR TEKNIS
        // =========================
        SatsetErrorRespon::create([
            'uuid'          => $noreg,
            'response'      => $send,
            'jenis'         => 'ranap',
            'error_summary' => 'Gagal verifikasi NIK Pasien ke SatuSehat'
        ]);

        return [
            'message' => 'failed',
            'data'    => $send
        ];
    }

    public static function getPractitionerFromSatset($pasien)
    {
        $nik = $pasien->datasimpeg ? $pasien->datasimpeg['nik'] : null;
        $kdpeg = $pasien->kdpegsimrs ?? $pasien->nip ?? $pasien->id ?? '-';
        $namaDokter = $pasien->nama ?? '-';

        if (!$nik) {
            SatsetAuditDataLog::recordAudit([
                'kategori' => 'PEGAWAI_NIK_KOSONG',
                'unit' => 'kepegawaian',
                'ref_id' => (string)$kdpeg,
                'noreg' => $pasien->noreg ?? null,
                'nama' => $namaDokter,
                'nik_simrs' => null,
                'keterangan' => 'NIK Pegawai/Dokter (' . $namaDokter . ') belum diisi di SIMPEG / Master Pegawai'
            ]);
            return ['message' => 'failed', 'data' => 'NIK Dokter Kosong'];
        }

        $token = AuthSatsetHelper::accessToken();
        $params = '/Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;

        $send = BridgingSatsetHelper::get_data($token, $params);

        $data = Pegawai::where('nik', $nik)->where('aktif', 'AKTIF')->first();

        if ($send['message'] === 'success' && isset($send['data']['uuid'])) {
            if ($data) {
                $data->satset_uuid = $send['data']['uuid'];
                $data->save();
            }
        } else {
            SatsetErrorRespon::create([
                'uuid'          => $pasien->noreg ?? $kdpeg,
                'response'      => $send,
                'jenis'         => 'ranap',
                'error_summary' => 'Practitioner NIK Dokter tidak ditemukan di SatuSehat Kemkes (NIK: ' . $nik . ')'
            ]);

            SatsetAuditDataLog::recordAudit([
                'kategori' => 'PEGAWAI_UNREGISTERED_SATSET',
                'unit' => 'kepegawaian',
                'ref_id' => (string)$kdpeg,
                'noreg' => $pasien->noreg ?? null,
                'nama' => $namaDokter,
                'nik_simrs' => $nik,
                'keterangan' => 'Pegawai/Dokter (' . $namaDokter . ' - NIK: ' . $nik . ') tidak ditemukan/belum terdaftar di SatuSehat Kemkes'
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
        $encounter_uuid = self::generateUuid();
        $practitioner_uuid = $request->datasimpeg['satset_uuid'] ?? null;
        $tgl_kunjungan = $request->tglmasuk;

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

        // 2. Tindakan (Procedure)
        if (isset($request->tindakan) && count($request->tindakan) > 0) {
            $procedures = PostKunjunganRajalHelper::procedure($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid);
            if (is_array($procedures)) {
                foreach ($procedures as $proc) {
                    if (!empty($proc)) {
                        $form['entry'][] = $proc;
                    }
                }
            }
        }

        // 3. Tambahkan Radiologi (Menggunakan data dari $request)
        if (isset($request->radiologi) && count($request->radiologi) > 0) {
            $res_radiologi = self::radiologi($request, $pasien_uuid, $encounter_uuid, $organization_id);
            foreach ($res_radiologi as $rad_entry) {
                $form['entry'][] = $rad_entry;
            }
        }

        // 4. Laborat (Specimen, ServiceRequest, Observation, DiagnosticReport)
        if (isset($request->laborats) && count($request->laborats) > 0) {
            $specimenSnomeds = Msnomed::whereNotNull('spesimen')->get();
            $lab_entries = PostKunjunganRajalHelper::laborats($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id, $specimenSnomeds);
            if (is_array($lab_entries)) {
                for ($i = 0; $i < count($lab_entries); $i++) {
                    $serviceRequest = $lab_entries[$i]['serviceRequests'] ?? null;
                    $hasil = $lab_entries[$i]['hasil'] ?? null;
                    $spesimen = $lab_entries[$i]['spesimen'] ?? null;
                    $diagnosticReport = $lab_entries[$i]['diagnosticReport'] ?? null;

                    if ($serviceRequest !== null) $form['entry'][] = $serviceRequest;
                    if ($hasil !== null) $form['entry'][] = $hasil;
                    if ($spesimen !== null) $form['entry'][] = $spesimen;
                    if ($diagnosticReport !== null) $form['entry'][] = $diagnosticReport;
                }
            }
        }

        // 5. Farmasi (Medication, MedicationRequest, MedicationDispense)
        if (isset($request->apotek) && count($request->apotek) > 0) {
            $apotek_entries = PostKunjunganRajalHelper::apotek($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id);
            if (isset($apotek_entries['nonracikan']) && is_array($apotek_entries['nonracikan'])) {
                for ($i = 0; $i < count($apotek_entries['nonracikan']); $i++) {
                    if (!empty($apotek_entries['nonracikan'][$i]['medication'])) $form['entry'][] = $apotek_entries['nonracikan'][$i]['medication'];
                    if (!empty($apotek_entries['nonracikan'][$i]['medication_request'])) $form['entry'][] = $apotek_entries['nonracikan'][$i]['medication_request'];
                    if (!empty($apotek_entries['nonracikan'][$i]['medicationD'])) $form['entry'][] = $apotek_entries['nonracikan'][$i]['medicationD'];
                    if (!empty($apotek_entries['nonracikan'][$i]['medication_dispense'])) $form['entry'][] = $apotek_entries['nonracikan'][$i]['medication_dispense'];
                }
            }
            if (isset($apotek_entries['racikan']) && is_array($apotek_entries['racikan'])) {
                for ($i = 0; $i < count($apotek_entries['racikan']); $i++) {
                    if (!empty($apotek_entries['racikan'][$i]['medication'])) $form['entry'][] = $apotek_entries['racikan'][$i]['medication'];
                    if (!empty($apotek_entries['racikan'][$i]['medication_request'])) $form['entry'][] = $apotek_entries['racikan'][$i]['medication_request'];
                    if (!empty($apotek_entries['racikan'][$i]['medicationD'])) $form['entry'][] = $apotek_entries['racikan'][$i]['medicationD'];
                    if (!empty($apotek_entries['racikan'][$i]['medication_dispense'])) $form['entry'][] = $apotek_entries['racikan'][$i]['medication_dispense'];
                }
            }
        }

        // 6. Imunisasi
        $imunization = self::imunisasi($request, $pasien_uuid, $encounter_uuid, $organization_id);
        if (!empty($imunization)) {
            $form['entry'][] = $imunization;
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
                    "encounter" => ["reference" => "urn:uuid:" . $encounter_uuid],
                    "onsetDateTime" => $start,
                ],
                "request" => ["method" => "POST", "url" => "Condition"]
            ];
        }

        // B. Data Lokasi (Bangsal)
        $ruangId = $request->relmasterruangranap->ruang->satset_uuid ?? $request->relmasterruangranap['ruang']['satset_uuid'] ?? null;
        if (empty($ruangId) || $ruangId === '00000000-0000-0000-0000-000000000000') {
            $kodeRuang = $request->relmasterruangranap->kode_ruang ?? null;
            if ($kodeRuang) {
                $locRecord = DB::table('satsets')
                    ->where('resource', 'Location')
                    ->where('response', 'like', '%"value":"' . $kodeRuang . '"%')
                    ->first(['uuid']);
                if ($locRecord && !empty($locRecord->uuid)) {
                    $ruangId = $locRecord->uuid;
                }
            }
        }
        if (empty($ruangId) || $ruangId === '00000000-0000-0000-0000-000000000000') {
            $ruangId = '0251eaf2-295f-4e87-9fb8-b180e743db00'; // Default Ruang Rawat Inap RSUD Mohamad Saleh
        }
        $lantai = $request->relmasterruangranap->ruang->lantai ?? $request->relmasterruangranap['ruang']['lantai'] ?? '1';
        $gedung = $request->relmasterruangranap->ruang->gedung ?? $request->relmasterruangranap['ruang']['gedung'] ?? '1';

        // C. Perakit Resource Encounter (IMP)
        $formEncounter = [
            "fullUrl" => "urn:uuid:" . $encounter_uuid,
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
                "reference" => "Location/" . ($ruangId ?: '00000000-0000-0000-0000-000000000000'), // Gunakan ID Valid atau Default
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
                $modality = !empty($rincian['relmasterpemeriksaan']['modality']) ? $rincian['relmasterpemeriksaan']['modality'] : 'CR';
                $nama_foto = !empty($rincian['relmasterpemeriksaan']['rs2']) ? $rincian['relmasterpemeriksaan']['rs2'] : ($rincian['pemeriksaan'] ?? 'Pemeriksaan Radiologi');
                $study_uid = $rincian['study_instance_uid'] ?? null;
                $hasil_expertise = $rincian['hasil'] ?? null;

                $loinc_code = !empty($rincian['relmasterpemeriksaan']['loinc_code']) ? $rincian['relmasterpemeriksaan']['loinc_code'] : '24648-8';
                $loinc_display = !empty($rincian['relmasterpemeriksaan']['loinc_display']) ? $rincian['relmasterpemeriksaan']['loinc_display'] : 'Chest XR';

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
                        "encounter" => ["reference" => "urn:uuid:" . $encounter_uuid],
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
                            "encounter" => ["reference" => "urn:uuid:" . $encounter_uuid],
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
                            "encounter" => ["reference" => "urn:uuid:" . $encounter_uuid],
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
                            "encounter" => ["reference" => "urn:uuid:" . $encounter_uuid],
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

    public static function imunisasi($request, $pasien_uuid, $encounter_uuid, $organization_id)
    {
        $imunisasi = collect($request->nursenote)
            ->pluck('reseps')
            ->flatten(1)
            ->first(function ($item) {

                $text = strtolower(
                    ($item['nama_obat'] ?? '') . ' ' .
                        ($item['kandungan'] ?? '')
                );

                return str_contains($text, 'hb0')
                    || str_contains($text, 'hepatitis b')
                    || str_contains($text, 'vaksin hb0');
            });

        // Carbon::parse($request->tglmasuk)->toIso8601String();
        // return $imunisasi;
        $form = [];

        // B. Data Lokasi (Bangsal)
        $ruangId = $request->relmasterruangranap->ruang->satset_uuid ?? $request->relmasterruangranap['ruang']['satset_uuid'] ?? null;
        $lantai = $request->relmasterruangranap->ruang->lantai ?? $request->relmasterruangranap['ruang']['lantai'] ?? '-';
        $gedung = $request->relmasterruangranap->ruang->gedung ?? $request->relmasterruangranap['ruang']['gedung'] ?? '-';

        $practitioner_uuid = $request?->datasimpeg?->satset_uuid;

        if ($imunisasi) {
            $form =
                [
                    "fullUrl" => "urn:uuid:" . self::generateUuid(),
                    "resource" => [
                        "resourceType" => "Immunization",
                        "status" => "completed",
                        "vaccineCode" => [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/cvx",
                                    "code" => "93",
                                    "display" => "Hepatitis B"
                                ]
                            ]
                        ],
                        // "reasonCode" => [
                        //     "coding" => [
                        //         [
                        //             "system" => "http://terminology.kemkes.go.id/CodeSystem/immunization-routine-timing",
                        //             "code" => "IM-Ideal",
                        //             "display" => "Imunisasi Ideal"
                        //         ]
                        //     ]
                        // ],

                        // === PERBAIKAN DISINI ===
                        "reasonCode" => [
                            [
                                "coding" => [
                                    [
                                        "system" => "http://terminology.kemkes.go.id/CodeSystem/immunization-routine-timing",
                                        "code" => "IM-Ideal",
                                        "display" => "Imunisasi Ideal"
                                    ]
                                ]
                            ]
                        ],

                        "patient" => [
                            "reference" => "Patient/" . $pasien_uuid,
                            "display" => $request->nama_panggil
                        ],

                        "encounter" => [
                            "reference" => "urn:uuid:" . $encounter_uuid   // ← Harus pakai urn:uuid
                        ],

                        "occurrenceDateTime" => Carbon::parse($imunisasi['created_at'])->toIso8601String(),

                        "primarySource" => true,
                        "lotNumber" => $imunisasi['kdobat'],
                        "expirationDate" => date('Y-m-d', strtotime('+18 months')),
                        "location" => [
                            "reference" => "Location/" . ($ruangId ?: '00000000-0000-0000-0000-000000000000'), // Gunakan ID Valid atau Default
                            "display" => "Bed $request->nomorbed, $request->ruangan, Lantai $lantai Gedung $gedung"
                        ],

                        "performer" => [
                            [
                                "function" => [
                                    "coding" => [
                                        [
                                            "system" => "http://terminology.hl7.org/CodeSystem/v2-0443",
                                            "code" => "AP",                    // ← WAJIB
                                            "display" => "Administering Provider"
                                        ]
                                    ]
                                ],
                                "actor" => [
                                    "reference" => "Practitioner/" . $practitioner_uuid
                                ]
                            ]
                        ],

                        "protocolApplied" => [
                            [
                                "doseNumberPositiveInt" => 1,
                                "series" => "Hepatitis B"
                            ]
                        ]
                    ],

                    "request" => ["method" => "POST", "url" => "Immunization"]
                ];
        }
        return $form;
    }
}
