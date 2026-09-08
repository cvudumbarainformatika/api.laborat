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
    public static function generateUuid()
    {
        return (string) Str::orderedUuid();
    }

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
            'rs23.rs23 as carakeluar',
            'rs23.rs24 as prognosis',
            'rs23.rs25 as sebabkematian',
            'rs23.rs26 as diagakhir',
            'rs26.rs2 as prognosa',
            'rs21.rs2 as dokter',
            'rs23.rs19 as kodesistembayar',
            'rs23.rs22 as status',
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
            ->leftjoin('rs26', 'rs26.rs1', 'rs23.rs23')
            ->leftjoin('rs242', 'rs242.rs1', 'rs23.rs1')
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
                'pemeriksaan' => function ($q) {
                    $q->select([
                        'rs253.id',
                        'rs253.rs1',
                        'rs253.rs1 as noreg',
                        'rs253.rs2 as norm',
                        'rs253.rs3 as tgl',
                        'rs253.rs4 as ruang',
                        'rs253.pernapasan',
                        'rs253.nadi',
                        'rs253.tensi',
                        'rs253.beratbadan',
                        'rs253.tinggibadan',
                        'rs253.kdruang',
                        'rs253.user',
                        'rs253.awal',
                        'sambung.keadaanUmum',
                        'sambung.bb',
                        'sambung.tb',
                        'sambung.nadi as nadi_sambung',
                        'sambung.suhu',
                        'sambung.sistole',
                        'sambung.diastole',
                        'sambung.pernapasan as pernapasan_sambung',
                        'sambung.spo',
                        'sambung.tkKesadaran',
                        'sambung.tkKesadaranKet',
                    ])
                        ->leftJoin('rs253_sambung as sambung', 'rs253.id', '=', 'sambung.rs253_id')
                        ->orderBy('rs253.id', 'DESC');
                },
                'penilaian' => function ($q) {
                    $q->select([
                        'id',
                        'rs1',
                        'rs1 as noreg',
                        'rs2 as norm',
                        'rs3 as tgl',
                        'barthel',
                        'norton',
                        'humpty_dumpty',
                        'morse_fall',
                        'ontario',
                        'edmonson',
                        'user',
                        'kdruang',
                        'awal',
                        'group_nakes'
                    ])->orderBy('id', 'DESC');
                },
                'anamnesis' => function ($q) {
                    $q->select([
                        'rs209.id',
                        'rs209.rs1',
                        'rs209.rs1 as noreg',
                        'rs209.rs2 as norm',
                        'rs209.rs3 as tgl',
                        'rs209.rs4 as keluhanUtama',
                        'rs209.riwayatpenyakit',
                        'rs209.riwayatalergi',
                        'rs209.keteranganalergi',
                        'rs209.riwayatpengobatan',
                        'rs209.riwayatpenyakitsekarang',
                        'rs209.riwayatpenyakitkeluarga',
                        'rs209.kdruang',
                        'rs209.awal',
                        'rs209.user',
                    ])->orderBy('rs209.id', 'DESC');
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
                'edukasi' => function ($q) {
                    $q->orderBy('id', 'DESC');
                },
                'dischargeplanning',
                'planningdokter',
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
            ->whereIn('rs23.rs22', ['2', '3']) // Status sudah pulang
            ->has('diagnosa');

        if ($tgl) {
            $select->where('rs23.rs4', 'LIKE', $tgl . '%');
        } else {
            $tglAwal = Carbon::now()->subDays(60)->toDateString() . ' 00:00:00';
            $tglAkhir = Carbon::now()->subDays(1)->toDateString() . ' 23:59:59';
            $select->whereBetween('rs23.rs4', [$tglAwal, $tglAkhir]);
        }

        $data = $select
            ->doesntHave('satset')
            ->doesntHave('satset_error')
            ->orderBy('rs23.rs4', 'asc')
            ->first();

        return self::kirimKunjunganRanap($data);
    }

    public static function cobaRanap($noreg)
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
            'rs23.rs23 as carakeluar',
            'rs23.rs24 as prognosis',
            'rs23.rs25 as sebabkematian',
            'rs23.rs26 as diagakhir',
            'rs26.rs2 as prognosa',
            'rs21.rs2 as dokter',
            'rs23.rs19 as kodesistembayar',
            'rs23.rs22 as status',
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
            ->leftjoin('rs26', 'rs26.rs1', 'rs23.rs23')
            ->leftjoin('rs242', 'rs242.rs1', 'rs23.rs1')
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
                'pemeriksaan' => function ($q) {
                    $q->select([
                        'rs253.id',
                        'rs253.rs1',
                        'rs253.rs1 as noreg',
                        'rs253.rs2 as norm',
                        'rs253.rs3 as tgl',
                        'rs253.rs4 as ruang',
                        'rs253.pernapasan',
                        'rs253.nadi',
                        'rs253.tensi',
                        'rs253.beratbadan',
                        'rs253.tinggibadan',
                        'rs253.kdruang',
                        'rs253.user',
                        'rs253.awal',
                        'sambung.keadaanUmum',
                        'sambung.bb',
                        'sambung.tb',
                        'sambung.nadi as nadi_sambung',
                        'sambung.suhu',
                        'sambung.sistole',
                        'sambung.diastole',
                        'sambung.pernapasan as pernapasan_sambung',
                        'sambung.spo',
                        'sambung.tkKesadaran',
                        'sambung.tkKesadaranKet',
                    ])
                        ->leftJoin('rs253_sambung as sambung', 'rs253.id', '=', 'sambung.rs253_id')
                        ->orderBy('rs253.id', 'DESC');
                },
                'penilaian' => function ($q) {
                    $q->select([
                        'id',
                        'rs1',
                        'rs1 as noreg',
                        'rs2 as norm',
                        'rs3 as tgl',
                        'barthel',
                        'norton',
                        'humpty_dumpty',
                        'morse_fall',
                        'ontario',
                        'edmonson',
                        'user',
                        'kdruang',
                        'awal',
                        'group_nakes'
                    ])->orderBy('id', 'DESC');
                },
                'anamnesis' => function ($q) {
                    $q->select([
                        'rs209.id',
                        'rs209.rs1',
                        'rs209.rs1 as noreg',
                        'rs209.rs2 as norm',
                        'rs209.rs3 as tgl',
                        'rs209.rs4 as keluhanUtama',
                        'rs209.riwayatpenyakit',
                        'rs209.riwayatalergi',
                        'rs209.keteranganalergi',
                        'rs209.riwayatpengobatan',
                        'rs209.riwayatpenyakitsekarang',
                        'rs209.riwayatpenyakitkeluarga',
                        'rs209.kdruang',
                        'rs209.awal',
                        'rs209.user',
                    ])->orderBy('rs209.id', 'DESC');
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
                'edukasi' => function ($q) {
                    $q->orderBy('id', 'DESC');
                },
                'dischargeplanning',
                'planningdokter',
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
            ->whereIn('rs23.rs22', ['2', '3'])
            ->first();

        return self::kirimKunjunganRanap($select);
    }

    public static function kirimKunjunganRanap($data)
    {
        if (!$data) {
            return ['message' => 'error', 'data' => 'Data Kunjungan Rawat Inap tidak ditemukan atau sudah pernah dikirim.'];
        }
        $pasien_uuid = $data?->pasien_uuid ?? null;
        $practitioner_uuid = $data?->datasimpeg?->satset_uuid;

        if (!$pasien_uuid) {
            $result = self::getPasienByNikSatset($data);

            if ($result['message'] === 'success') {
                $pasien_uuid = $result['uuid'];
            }

            if ($result['message'] === 'not-found') {
                $create = self::createPatientSatset($data);
                if (($create['message'] ?? null) === 'success') {
                    $pasien_uuid = $create['uuid'];
                }
            }
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

        // Validasi Diagnosa: JANGAN KIRIM jika diagnosa di SIMRS belum diisi
        if (empty($data->diagnosa) || count($data->diagnosa) === 0) {
            $err = [
                'method' => 'POST',
                'url' => 'https://api-satusehat.kemkes.go.id/fhir-r4/v1',
                'response' => ['message' => 'Diagnosa Dokter Belum Diisi di SIMRS (Pengiriman Dibatalkan)'],
                'uuid' => $data->noreg,
                'jenis' => 'ranap',
                'error_summary' => 'Diagnosa Dokter Belum Diisi di SIMRS',
            ];
            SatsetErrorRespon::create($err);
            return ['message' => 'failed', 'data' => 'Diagnosa Dokter Belum Diisi di SIMRS'];
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

        if (!empty($nik) && strlen($nik) === 16 && !str_starts_with($nik, '8888') && !str_starts_with($nik, '9999') && !str_starts_with($nik, '0000')) {
            try {
                $res = BridgingbpjsHelper::get_url('vclaim', 'Peserta/nik/' . $nik . '/tglSEP/' . $tglSep);
                if (isset($res['result']->peserta) && !empty($res['result']->peserta->nik)) {
                    $bpjsPeserta = $res['result']->peserta;
                }
            } catch (\Throwable $e) {}
        }

        if (!$bpjsPeserta && !empty($noka) && strlen($noka) >= 10) {
            try {
                $res = BridgingbpjsHelper::get_url('vclaim', 'Peserta/nokartu/' . $noka . '/tglSEP/' . $tglSep);
                if (isset($res['result']->peserta) && !empty($res['result']->peserta->nik)) {
                    $bpjsPeserta = $res['result']->peserta;
                }
            } catch (\Throwable $e) {}
        }

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
            $isBayi = !empty($tgllahir) && Carbon::parse($tgllahir)->diffInYears(now()) < 1;

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
                                    ["url" => "city", "valueCode" => (string)$cityCode],
                                    ["url" => "district", "valueCode" => (string)$distCode],
                                    ["url" => "village", "valueCode" => (string)$villCode]
                                ]
                            ]
                        ]
                    ]
                ],
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

            return [
                'message' => 'failed',
                'data' => $send
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
        if (!$pasien) {
            return ['message' => 'failed', 'data' => 'Data pasien kosong'];
        }
        $nik = trim((string)($pasien->nik ?? $pasien->rs49 ?? ''));
        $norm = $pasien->norm ?? $pasien->rs1 ?? null;
        $noreg = $pasien->noreg ?? $pasien->rs1 ?? null;

        if (empty($nik) || strlen($nik) !== 16 || str_starts_with($nik, '8888') || str_starts_with($nik, '9999')) {
            $bpjs = self::fetchBpjsPeserta($pasien);
            if ($bpjs && !empty($bpjs->nik)) {
                $nik = trim((string)$bpjs->nik);
            }
        }

        $token = AuthSatsetHelper::accessToken();
        $params = '/Patient?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;

        $send = BridgingSatsetHelper::get_data($token, $params);

        $data = Pasien::where([
            ['rs49', $nik],
            ['rs1', $norm],
        ])->first();

        if (isset($send['data']['response']['total'])) {
            $total = $send['data']['response']['total'];

            if ($total > 0) {
                $entry = $send['data']['response']['entry'][0]['resource'] ?? null;
                if ($entry) {
                    if ($data) {
                        $data->satset_uuid = $entry['id'];
                        $data->save();
                    }
                    return [
                        'message' => 'success',
                        'exists' => true,
                        'uuid' => $entry['id']
                    ];
                }
            }

            return [
                'message' => 'not-found',
                'exists' => false,
                'uuid' => null
            ];
        }

        SatsetErrorRespon::create([
            'uuid' => $noreg,
            'response' => $send,
            'jenis' => 'ranap',
            'error_summary' => 'Gagal verifikasi NIK Pasien ke SatuSehat'
        ]);

        return [
            'message' => 'failed',
            'data' => $send
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
                'uuid' => $pasien->noreg ?? $kdpeg,
                'response' => $send,
                'jenis' => 'ranap',
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

    public static function form($request, $pasien_uuid)
    {
        $organization_id = BridgingSatsetHelper::organization_id();
        $encounter_uuid = self::generateUuid();
        $practitioner_uuid = $request->datasimpeg['satset_uuid'] ?? null;
        $tgl_kunjungan = $request->tglmasuk;
        $specimenSnomeds = Msnomed::whereNotNull('spesimen')->get();

        $form = [
            "resourceType" => "Bundle",
            "type" => "transaction",
            "entry" => []
        ];

        // 1. Encounter & Condition (Diagnosis Primer, Sekunder, & Status Stabil)
        $res_ec = self::encounterRanap($request, $pasien_uuid, $organization_id, $encounter_uuid);
        $form['entry'][] = $res_ec['encounter'];
        foreach ($res_ec['condition'] as $cond) {
            $form['entry'][] = $cond;
        }
        $condPrimerUuid = $res_ec['cond_primer_uuid'] ?? null;
        $diagPrimer = $res_ec['diag_primer'] ?? null;

        // 2. CarePlan (Rencana Rawat, Instruksi Medik, Discharge Care Plan)
        $carePlans = self::carePlanRanap($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid);
        if (!empty($carePlans)) {
            $carePlansUnique = collect($carePlans)->unique('fullUrl')->all();
            foreach ($carePlansUnique as $cp) {
                if ($cp !== null) $form['entry'][] = $cp;
            }
        }

        // 3. Observation (TTV, Kesadaran, Risiko Jatuh Multi-Scale, Rencana Pulang)
        $observations = self::observationRanap($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid);
        if (!empty($observations)) {
            $obsUnique = collect($observations)->unique('fullUrl')->all();
            foreach ($obsUnique as $obs) {
                if ($obs !== null) $form['entry'][] = $obs;
            }
        }

        // 4. Procedure (Terapetik / Tindakan ICD-9, Edukasi, Pra-Lab)
        $procedures = self::procedureRanap($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid);
        if (!empty($procedures)) {
            foreach ($procedures as $proc) {
                if (!empty($proc)) {
                    $form['entry'][] = $proc;
                }
            }
        }

        // 5. ClinicalImpression (Prognosis Pasien)
        $clinicalImpression = self::clinicalImpressionRanap($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id, $condPrimerUuid, $diagPrimer);
        if (!empty($clinicalImpression)) {
            $form['entry'][] = $clinicalImpression;
        }

        // 6. ServiceRequest (Kontrol Pasca Rawat Inap)
        $serviceRequestKontrol = self::serviceRequestKontrolRanap($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id, $diagPrimer);
        if (!empty($serviceRequestKontrol)) {
            $form['entry'][] = $serviceRequestKontrol;
        }

        // 7. Radiologi (ServiceRequest, ImagingStudy, Observation, DiagnosticReport)
        if (isset($request->radiologi) && count($request->radiologi) > 0) {
            $res_radiologi = self::radiologi($request, $pasien_uuid, $encounter_uuid, $organization_id);
            foreach ($res_radiologi as $rad_entry) {
                $form['entry'][] = $rad_entry;
            }
        }

        // 8. Laborat (ServiceRequest, Specimen, Observation, DiagnosticReport)
        if (isset($request->laborats) && count($request->laborats) > 0) {
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

        // 9. Farmasi (Medication, MedicationRequest Inpatient, MedicationDispense Inpatient, QuestionnaireResponse Kajian Resep Q0007)
        if (isset($request->apotek) && count($request->apotek) > 0) {
            $farmasiEntries = self::apotekRanap($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id, $condPrimerUuid, $diagPrimer);
            foreach ($farmasiEntries as $farmasiEntry) {
                if (!empty($farmasiEntry)) {
                    $form['entry'][] = $farmasiEntry;
                }
            }
        }

        // 10. Imunisasi (Jika Ada)
        $imunization = self::imunisasi($request, $pasien_uuid, $encounter_uuid, $organization_id);
        if (!empty($imunization)) {
            $form['entry'][] = $imunization;
        }

        // 11. Composition (Ringkasan Pulang Pasien Rawat Inap / Discharge Summary)
        $composition = self::compositionRanap($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id, $condPrimerUuid, $diagPrimer, $res_ec['condition'] ?? [], $procedures ?? []);
        if (!empty($composition)) {
            $form['entry'][] = $composition;
        }

        return ['message' => 'success', 'data' => $form];
    }

    public static function encounterRanap($request, $pasien_uuid, $organization_id, $encounter_uuid)
    {
        $start = Carbon::parse($request->tglmasuk)->toIso8601String();
        $tgl_keluar_raw = $request->tglkeluar;
        $is_pulang = ($tgl_keluar_raw && $tgl_keluar_raw != '0000-00-00 00:00:00' && !str_contains($tgl_keluar_raw, '-0001'));

        $status = $is_pulang ? 'finished' : 'in-progress';
        $end = $is_pulang ? Carbon::parse($tgl_keluar_raw)->toIso8601String() : $start;

        // A. Diagnosa Primer & Sekunder
        $diagnosa_entries = [];
        $condition_entries = [];
        $diagnosas = $request->diagnosa ?? [];
        $cond_primer_uuid = null;
        $diag_primer = null;

        if (empty($diagnosas) || count($diagnosas) === 0) {
            $diagAkhir = !empty($request->diagakhir) ? $request->diagakhir : 'Pemeriksaan Rawat Inap';
            $diagnosas = [
                [
                    'kode' => 'Z00.0',
                    'inggris' => 'General medical examination',
                    'indonesia' => $diagAkhir,
                ]
            ];
        }

        foreach ($diagnosas as $key => $val) {
            $cond_uuid = "urn:uuid:" . self::generateUuid();
            if ($key === 0) {
                $cond_primer_uuid = $cond_uuid;
                $diag_primer = $val;
            }
            $diagName = $val['inggris'] ?? $val['indonesia'] ?? 'Diagnosis';

            $diagnosa_entries[] = [
                "condition" => [
                    "reference" => $cond_uuid,
                    "display" => $diagName
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

            $condition_entries[] = [
                "fullUrl" => $cond_uuid,
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
                                "code" => $val['kode'],
                                "display" => $diagName
                            ]
                        ],
                        "text" => $val['indonesia'] ?? $diagName
                    ],
                    "subject" => [
                        "reference" => "Patient/$pasien_uuid",
                        "display" => $request->nama ?? $request->nama_panggil
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:" . $encounter_uuid
                    ],
                    "onsetDateTime" => $start,
                    "recordedDate" => $start,
                    "note" => [
                        [
                            "text" => "Diagnosis rawat inap: " . ($val['indonesia'] ?? $diagName)
                        ]
                    ]
                ],
                "request" => ["method" => "POST", "url" => "Condition"]
            ];
        }

        // B. Condition Status Stabil (Saat Pulang)
        $condStabilUuid = "urn:uuid:" . self::generateUuid();
        $condition_entries[] = [
            "fullUrl" => $condStabilUuid,
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
                                "code" => "problem-list-item",
                                "display" => "Problem List Item"
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://snomed.info/sct",
                            "code" => "359746009",
                            "display" => "Patient's condition stable"
                        ]
                    ]
                ],
                "subject" => [
                    "reference" => "Patient/$pasien_uuid",
                    "display" => $request->nama ?? $request->nama_panggil
                ],
                "encounter" => [
                    "reference" => "urn:uuid:" . $encounter_uuid,
                    "display" => "Kunjungan Rawat Inap " . ($request->nama ?? $request->nama_panggil)
                ]
            ],
            "request" => ["method" => "POST", "url" => "Condition"]
        ];

        // C. Data Lokasi (Bangsal Rawat Inap)
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
            $ruangId = '0251eaf2-295f-4e87-9fb8-b180e743db00';
        }
        $lantai = $request->relmasterruangranap->ruang->lantai ?? $request->relmasterruangranap['ruang']['lantai'] ?? '1';
        $gedung = $request->relmasterruangranap->ruang->gedung ?? $request->relmasterruangranap['ruang']['gedung'] ?? 'Gedung Utama';

        $kelasMapping = [
            'VVIP' => 'vip',
            'VIP' => 'vip',
            '1' => '1',
            '2' => '2',
            '3' => '3'
        ];
        $kodeKelasSS = $kelasMapping[$request->kelasruangan] ?? '3';

        $location_display = "Bed " . ($request->nomorbed ?? '-') . ", " . ($request->ruangan ?? 'Ruang Rawat Inap') . ", Lantai " . $lantai . ", " . $gedung;

        $loc_entry = [
            "extension" => [
                [
                    "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass",
                    "extension" => [
                        [
                            "url" => "value",
                            "valueCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" => "http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Inpatient",
                                        "code" => $kodeKelasSS,
                                        "display" => "Kelas " . ($request->kelasruangan ?? '3')
                                    ]
                                ]
                            ]
                        ],
                        [
                            "url" => "upgradeClassIndicator",
                            "valueCodeableConcept" => [
                                "coding" => [
                                    [
                                        "system" => "http://terminology.kemkes.go.id/CodeSystem/locationUpgradeClass",
                                        "code" => "kelas-tetap",
                                        "display" => "Kelas Tetap Perawatan"
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            "location" => [
                "reference" => "Location/" . $ruangId,
                "display" => $location_display
            ],
            "period" => [
                "start" => $start,
                "end" => $end
            ]
        ];

        // D. Hospitalization (Discharge Disposition)
        $carakeluar = $request->carakeluar ?? '';
        $dispCode = 'home';
        $dispDisplay = 'Home';
        $dispText = 'Anjuran dokter untuk pulang dan kontrol kembali.';

        if ($carakeluar === 'C002' || $carakeluar === 'C004' || $carakeluar === 'C010') {
            $dispCode = 'aadvice';
            $dispDisplay = 'Left against medical advice';
            $dispText = 'Pasien pulang atas permintaan sendiri.';
        } elseif ($carakeluar === 'C003') {
            $dispCode = 'exp';
            $dispDisplay = 'Expired';
            $dispText = 'Pasien dinyatakan meninggal dunia.';
        } elseif (in_array($carakeluar, ['C005', 'C007', 'C008', 'C009', 'C014'])) {
            $dispCode = 'other-hcf';
            $dispDisplay = 'Other healthcare facility';
            $dispText = 'Rujuk ke fasilitas pelayanan kesehatan lain.';
        }

        if (!empty($request->tindaklanjut)) {
            $dispText = $request->tindaklanjut;
        }

        $hospitalization = [
            "dischargeDisposition" => [
                "coding" => [
                    [
                        "system" => "http://terminology.hl7.org/CodeSystem/discharge-disposition",
                        "code" => $dispCode,
                        "display" => $dispDisplay
                    ]
                ],
                "text" => $dispText
            ]
        ];

        // E. Resource Encounter
        $formEncounter = [
            "fullUrl" => "urn:uuid:" . $encounter_uuid,
            "resource" => [
                "resourceType" => "Encounter",
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/encounter/" . $organization_id,
                        "value" => $request->noreg ?? $request->rs1
                    ]
                ],
                "status" => $status,
                "statusHistory" => [
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
                "class" => [
                    "system" => "http://terminology.hl7.org/CodeSystem/v3-ActCode",
                    "code" => "IMP",
                    "display" => "inpatient encounter"
                ],
                "subject" => [
                    "reference" => "Patient/$pasien_uuid",
                    "display" => $request->nama ?? $request->nama_panggil
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
                            "reference" => "Practitioner/" . ($request->datasimpeg['satset_uuid'] ?? '-'),
                            "display" => $request->datasimpeg['nama'] ?? '-'
                        ]
                    ]
                ],
                "period" => [
                    "start" => $start,
                    "end" => $end
                ],
                "diagnosis" => $diagnosa_entries,
                "hospitalization" => $hospitalization,
                "location" => [$loc_entry],
                "serviceProvider" => [
                    "reference" => "Organization/" . $organization_id
                ]
            ],
            "request" => ["method" => "POST", "url" => "Encounter"]
        ];

        return [
            "encounter" => $formEncounter,
            "condition" => $condition_entries,
            "cond_primer_uuid" => $cond_primer_uuid,
            "diag_primer" => $diag_primer
        ];
    }

    public static function carePlanRanap($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid)
    {
        $carePlans = [];
        $tglCreated = Carbon::parse($tgl_kunjungan)->toIso8601String();
        $namaPasien = $request->nama ?? $request->nama_panggil ?? 'Pasien';

        // 1. CarePlan: Rencana Rawat Pasien
        $descRawat = "Pasien akan melakukan perawatan rawat inap, pemeriksaan penunjang diagnostik laboratorium dan radiologi, serta tindakan terapi medis.";
        if (!empty($request->planningdokter->planning)) {
            $descRawat = $request->planningdokter->planning;
        }

        $carePlans[] = [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "CarePlan",
                "status" => "active",
                "intent" => "plan",
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "736353004",
                                "display" => " Inpatient care plan"
                            ]
                        ]
                    ]
                ],
                "title" => "Rencana Rawat Pasien",
                "description" => $descRawat,
                "subject" => [
                    "reference" => "Patient/$pasien_uuid",
                    "display" => $namaPasien
                ],
                "encounter" => [
                    "reference" => "urn:uuid:$encounter_uuid"
                ],
                "created" => $tglCreated,
                "author" => [
                    "reference" => "Practitioner/$practitioner_uuid"
                ]
            ],
            "request" => ["method" => "POST", "url" => "CarePlan"]
        ];

        // 2. CarePlan: Instruksi Medik dan Keperawatan Pasien
        $descInstruksi = "Instruksi medik dan keperawatan: monitoring tanda-tanda vital berkala, pemberian medikasi intravena/oral sesuai advis DPJP, asuhan keperawatan dan nutrisi optimal.";
        if (!empty($request->planningdokter->instruksidokter)) {
            $descInstruksi = $request->planningdokter->instruksidokter;
        }

        $carePlans[] = [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "CarePlan",
                "status" => "active",
                "intent" => "plan",
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "736353004",
                                "display" => " Inpatient care plan"
                            ]
                        ]
                    ]
                ],
                "title" => "Instruksi Medik dan Keperawatan Pasien",
                "description" => $descInstruksi,
                "subject" => [
                    "reference" => "Patient/$pasien_uuid",
                    "display" => $namaPasien
                ],
                "encounter" => [
                    "reference" => "urn:uuid:$encounter_uuid"
                ],
                "created" => $tglCreated,
                "author" => [
                    "reference" => "Practitioner/$practitioner_uuid"
                ]
            ],
            "request" => ["method" => "POST", "url" => "CarePlan"]
        ];

        // 3. CarePlan: Perencanaan Pemulangan Pasien (Discharge Care Plan)
        $tglPulangCreated = $request->tglkeluar ? Carbon::parse($request->tglkeluar)->toIso8601String() : $tglCreated;
        $descDischarge = "Perencanaan pemulangan pasien: pasien diperbolehkan pulang dengan edukasi minum obat teratur, kontrol rutin sesuai jadwal, dan segera kembali jika ada keluhan kegawatan.";
        if (!empty($request->tindaklanjut)) {
            $descDischarge = $request->tindaklanjut;
        }

        $carePlans[] = [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "CarePlan",
                "status" => "active",
                "intent" => "plan",
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "736372004",
                                "display" => "Discharge care plan"
                            ]
                        ]
                    ]
                ],
                "title" => "Perencanaan Pemulangan Pasien",
                "description" => $descDischarge,
                "subject" => [
                    "reference" => "Patient/$pasien_uuid",
                    "display" => $namaPasien
                ],
                "encounter" => [
                    "reference" => "urn:uuid:$encounter_uuid"
                ],
                "created" => $tglPulangCreated,
                "author" => [
                    "reference" => "Practitioner/$practitioner_uuid"
                ]
            ],
            "request" => ["method" => "POST", "url" => "CarePlan"]
        ];

        return $carePlans;
    }

    public static function observationRanap($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid)
    {
        $organization_id = BridgingSatsetHelper::organization_id();
        $tglEff = Carbon::parse($tgl_kunjungan)->toIso8601String();
        $namaPasien = $request->nama ?? $request->nama_panggil ?? 'Pasien';

        $pemeriksaan = count($request->pemeriksaan ?? []) > 0 ? $request->pemeriksaan[0] : null;

        $nadi = $pemeriksaan ? (int)($pemeriksaan->nadi_sambung ?? $pemeriksaan->nadi ?? 80) : 80;
        $pernapasan = $pemeriksaan ? (int)($pemeriksaan->pernapasan_sambung ?? $pemeriksaan->pernapasan ?? 20) : 20;
        $sistole = $pemeriksaan ? (int)($pemeriksaan->sistole ?? 120) : 120;
        $diastole = $pemeriksaan ? (int)($pemeriksaan->diastole ?? 80) : 80;
        $suhu = $pemeriksaan ? (float)($pemeriksaan->suhu ?? 36.5) : 36.5;
        $spo = $pemeriksaan ? (int)($pemeriksaan->spo ?? 98) : 98;

        // Kesadaran
        $tkKesadaranKet = strtolower($pemeriksaan->tkKesadaranKet ?? '');
        $tkKesadaran = $pemeriksaan->tkKesadaran ?? '';
        $snowmedKesadaran = ["code" => "248234008", "display" => "Mentally alert"];

        if (str_contains($tkKesadaranKet, 'voice') || $tkKesadaran === '1') {
            $snowmedKesadaran = ["code" => "300202002", "display" => "Response to voice"];
        } elseif (str_contains($tkKesadaranKet, 'pain') || $tkKesadaran === '2') {
            $snowmedKesadaran = ["code" => "450847001", "display" => "Responds to pain"];
        } elseif (str_contains($tkKesadaranKet, 'koma') || str_contains($tkKesadaranKet, 'unresponsive') || $tkKesadaran === '3') {
            $snowmedKesadaran = ["code" => "422768004", "display" => "Unresponsive"];
        } elseif (str_contains($tkKesadaranKet, 'delirium') || $tkKesadaran === '5') {
            $snowmedKesadaran = ["code" => "2776000", "display" => "Delirium"];
        }

        $entries = [];

        // 1. Observation Kesadaran
        $entries[] = [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "Observation",
                "status" => "final",
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "exam",
                                "display" => "Exam"
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "67775-7",
                            "display" => "Level of responsiveness"
                        ]
                    ]
                ],
                "subject" => ["reference" => "Patient/$pasien_uuid"],
                "encounter" => [
                    "reference" => "urn:uuid:$encounter_uuid",
                    "display" => "Pemeriksaan Kesadaran $namaPasien"
                ],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [["reference" => "Practitioner/$practitioner_uuid"]],
                "valueCodeableConcept" => [
                    "coding" => [
                        [
                            "system" => "http://snomed.info/sct",
                            "code" => $snowmedKesadaran['code'],
                            "display" => $snowmedKesadaran['display']
                        ]
                    ]
                ]
            ],
            "request" => ["method" => "POST", "url" => "Observation"]
        ];

        // 2. Observation Nadi (Heart rate)
        $entries[] = [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "Observation",
                "status" => "final",
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "vital-signs",
                                "display" => "Vital Signs"
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "8867-4",
                            "display" => "Heart rate"
                        ]
                    ]
                ],
                "subject" => ["reference" => "Patient/$pasien_uuid"],
                "encounter" => [
                    "reference" => "urn:uuid:$encounter_uuid",
                    "display" => "Pemeriksaan Fisik Nadi $namaPasien"
                ],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [["reference" => "Practitioner/$practitioner_uuid"]],
                "valueQuantity" => [
                    "value" => $nadi ?: 80,
                    "unit" => "beats/minute",
                    "system" => "http://unitsofmeasure.org",
                    "code" => "/min"
                ]
            ],
            "request" => ["method" => "POST", "url" => "Observation"]
        ];

        // 3. Observation Pernapasan (Respiratory rate)
        $entries[] = [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "Observation",
                "status" => "final",
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "vital-signs",
                                "display" => "Vital Signs"
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "9279-1",
                            "display" => "Respiratory rate"
                        ]
                    ]
                ],
                "subject" => ["reference" => "Patient/$pasien_uuid"],
                "encounter" => [
                    "reference" => "urn:uuid:$encounter_uuid",
                    "display" => "Pemeriksaan Pernapasan $namaPasien"
                ],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [["reference" => "Practitioner/$practitioner_uuid"]],
                "valueQuantity" => [
                    "value" => $pernapasan ?: 20,
                    "unit" => "breaths/minute",
                    "system" => "http://unitsofmeasure.org",
                    "code" => "/min"
                ]
            ],
            "request" => ["method" => "POST", "url" => "Observation"]
        ];

        // 4. Observation Sistol
        $entries[] = [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "Observation",
                "status" => "final",
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "vital-signs",
                                "display" => "Vital Signs"
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "8480-6",
                            "display" => "Systolic blood pressure"
                        ]
                    ]
                ],
                "subject" => ["reference" => "Patient/$pasien_uuid"],
                "encounter" => ["reference" => "urn:uuid:$encounter_uuid"],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [["reference" => "Practitioner/$practitioner_uuid"]],
                "valueQuantity" => [
                    "value" => $sistole ?: 120,
                    "unit" => "mm[Hg]",
                    "system" => "http://unitsofmeasure.org",
                    "code" => "mm[Hg]"
                ]
            ],
            "request" => ["method" => "POST", "url" => "Observation"]
        ];

        // 5. Observation Diastol
        $entries[] = [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "Observation",
                "status" => "final",
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "vital-signs",
                                "display" => "Vital Signs"
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "8462-4",
                            "display" => "Diastolic blood pressure"
                        ]
                    ]
                ],
                "subject" => ["reference" => "Patient/$pasien_uuid"],
                "encounter" => ["reference" => "urn:uuid:$encounter_uuid"],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [["reference" => "Practitioner/$practitioner_uuid"]],
                "valueQuantity" => [
                    "value" => $diastole ?: 80,
                    "unit" => "mm[Hg]",
                    "system" => "http://unitsofmeasure.org",
                    "code" => "mm[Hg]"
                ]
            ],
            "request" => ["method" => "POST", "url" => "Observation"]
        ];

        // 6. Observation Suhu
        $entries[] = [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "Observation",
                "status" => "final",
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "vital-signs",
                                "display" => "Vital Signs"
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "8310-5",
                            "display" => "Body temperature"
                        ]
                    ]
                ],
                "subject" => ["reference" => "Patient/$pasien_uuid"],
                "encounter" => ["reference" => "urn:uuid:$encounter_uuid"],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [["reference" => "Practitioner/$practitioner_uuid"]],
                "valueQuantity" => [
                    "value" => $suhu ?: 36.5,
                    "unit" => "C",
                    "system" => "http://unitsofmeasure.org",
                    "code" => "Cel"
                ]
            ],
            "request" => ["method" => "POST", "url" => "Observation"]
        ];

        // 7. Observation Risiko Jatuh (Multi-Scale)
        $fallScore = 0;
        $fallScaleLoinc = "59461-4";
        $fallScaleDisplay = "Fall risk level [Morse Fall Scale]";
        $interpretationCode = 'OI000026';
        $interpretationText = '0 - 24 (Risiko rendah)';

        if (count($request->penilaian ?? []) > 0) {
            $pen = $request->penilaian[0];
            $morseJson = is_string($pen->morse_fall) ? json_decode($pen->morse_fall, true) : $pen->morse_fall;
            $ontarioJson = is_string($pen->ontario) ? json_decode($pen->ontario, true) : $pen->ontario;
            $humptyJson = is_string($pen->humpty_dumpty) ? json_decode($pen->humpty_dumpty, true) : $pen->humpty_dumpty;
            $edmonsonJson = is_string($pen->edmonson) ? json_decode($pen->edmonson, true) : $pen->edmonson;

            if (!empty($ontarioJson) && (isset($ontarioJson['skorOntario']['skor']) || isset($ontarioJson['skor']))) {
                $fallScore = (int)($ontarioJson['skorOntario']['skor'] ?? $ontarioJson['skor'] ?? 0);
                $fallScaleLoinc = "75278-2";
                $fallScaleDisplay = "STRATIFY score [Ontario scale]";
                $label = $ontarioJson['skorOntario']['label'] ?? ($fallScore >= 17 ? 'Risiko tinggi' : ($fallScore >= 6 ? 'Risiko sedang' : 'Risiko rendah'));
                if (str_contains(strtolower($label), 'tinggi') || $fallScore >= 17) {
                    $interpretationCode = 'OI000028';
                    $interpretationText = '>= 17 (Risiko tinggi)';
                } elseif (str_contains(strtolower($label), 'sedang') || $fallScore >= 6) {
                    $interpretationCode = 'OI000027';
                    $interpretationText = '6 - 16 (Risiko sedang)';
                } else {
                    $interpretationCode = 'OI000026';
                    $interpretationText = '0 - 5 (Risiko rendah)';
                }
            } elseif (!empty($humptyJson) && (isset($humptyJson['skorHumpty']['skor']) || isset($humptyJson['skor']))) {
                $fallScore = (int)($humptyJson['skorHumpty']['skor'] ?? $humptyJson['skor'] ?? 0);
                $fallScaleLoinc = "75277-4";
                $fallScaleDisplay = "Humpty Dumpty fall risk assessment score";
                $label = $humptyJson['skorHumpty']['label'] ?? ($fallScore >= 12 ? 'Risiko tinggi' : 'Risiko rendah');
                if (str_contains(strtolower($label), 'tinggi') || $fallScore >= 12) {
                    $interpretationCode = 'OI000028';
                    $interpretationText = '>= 12 (Risiko tinggi)';
                } else {
                    $interpretationCode = 'OI000026';
                    $interpretationText = '7 - 11 (Risiko rendah)';
                }
            } elseif (!empty($morseJson) && (isset($morseJson['skorMorse']['skor']) || isset($morseJson['skor']))) {
                $fallScore = (int)($morseJson['skorMorse']['skor'] ?? $morseJson['skor'] ?? 0);
                $fallScaleLoinc = "59461-4";
                $fallScaleDisplay = "Fall risk level [Morse Fall Scale]";
                $label = $morseJson['skorMorse']['label'] ?? ($fallScore >= 45 ? 'Risiko tinggi' : ($fallScore >= 25 ? 'Risiko sedang' : 'Risiko rendah'));
                if (str_contains(strtolower($label), 'tinggi') || $fallScore >= 45) {
                    $interpretationCode = 'OI000028';
                    $interpretationText = '>= 45 (Risiko tinggi)';
                } elseif (str_contains(strtolower($label), 'sedang') || $fallScore >= 25) {
                    $interpretationCode = 'OI000027';
                    $interpretationText = '25 - 44 (Risiko sedang)';
                } else {
                    $interpretationCode = 'OI000026';
                    $interpretationText = '0 - 24 (Risiko rendah)';
                }
            } elseif (!empty($edmonsonJson)) {
                $fallScore = (int)($edmonsonJson['skor'] ?? 0);
                $fallScaleLoinc = "75280-8";
                $fallScaleDisplay = "Edmonson psychiatric fall risk assessment score";
                $interpretationCode = $fallScore >= 90 ? 'OI000028' : 'OI000026';
                $interpretationText = $fallScore >= 90 ? 'Risiko tinggi' : 'Risiko rendah';
            }
        }

        $entries[] = [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "Observation",
                "status" => "final",
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/observation/" . $organization_id,
                        "value" => ($request->noreg ?? $request->rs1) . "-FALL"
                    ]
                ],
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "exam",
                                "display" => "Exam"
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => $fallScaleLoinc,
                            "display" => $fallScaleDisplay
                        ]
                    ]
                ],
                "subject" => ["reference" => "Patient/$pasien_uuid"],
                "encounter" => ["reference" => "urn:uuid:$encounter_uuid"],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [["reference" => "Practitioner/$practitioner_uuid"]],
                "valueQuantity" => [
                    "value" => $fallScore,
                    "unit" => "{score}",
                    "system" => "http://unitsofmeasure.org",
                    "code" => "{score}"
                ],
                "interpretation" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                                "code" => $interpretationCode,
                                "display" => $interpretationText
                            ]
                        ],
                        "text" => str_contains($interpretationText, 'sedang') ? 'Risiko sedang' : (str_contains($interpretationText, 'tinggi') ? 'Risiko tinggi' : 'Risiko rendah')
                    ]
                ]
            ],
            "request" => ["method" => "POST", "url" => "Observation"]
        ];

        // 8. Observation Kriteria Rencana Pemulangan
        $entries[] = [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "Observation",
                "status" => "final",
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://terminology.hl7.org/CodeSystem/observation-category",
                                "code" => "survey",
                                "display" => "Survey"
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                            "code" => "OC000055",
                            "display" => "Kriteria Pasien yang dilakukan Rencana Pemulangan"
                        ]
                    ]
                ],
                "subject" => ["reference" => "Patient/$pasien_uuid"],
                "encounter" => [
                    "reference" => "urn:uuid:$encounter_uuid",
                    "display" => "Pemeriksaan Kriteria untuk Rencana Pemulangan $namaPasien"
                ],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [["reference" => "Practitioner/$practitioner_uuid"]],
                "valueCodeableConcept" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.kemkes.go.id/CodeSystem/clinical-term",
                            "code" => "OV000072",
                            "display" => "Pasien dengan perawatan berkelanjutan atau panjang"
                        ]
                    ]
                ]
            ],
            "request" => ["method" => "POST", "url" => "Observation"]
        ];

        return $entries;
    }

    public static function procedureRanap($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid)
    {
        $procedures = [];
        $tglStart = Carbon::parse($tgl_kunjungan)->toIso8601String();
        $tglEnd = Carbon::parse($tgl_kunjungan)->addMinutes(30)->toIso8601String();
        $namaPasien = $request->nama ?? $request->nama_panggil ?? 'Pasien';

        // 1. Prosedur Pra-Lab / Pra-Rad (Fasting / Non-fasting)
        $hasLab = count($request->laborats ?? []) > 0;
        $hasRad = count($request->radiologi ?? []) > 0;

        if ($hasLab || $hasRad) {
            $procedures[] = [
                "fullUrl" => "urn:uuid:" . self::generateUuid(),
                "resource" => [
                    "resourceType" => "Procedure",
                    "status" => "not-done",
                    "category" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "103693007",
                                "display" => "Diagnostic procedure"
                            ]
                        ]
                    ],
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "792805006",
                                "display" => "Fasting"
                            ]
                        ]
                    ],
                    "subject" => [
                        "reference" => "Patient/$pasien_uuid",
                        "display" => $namaPasien
                    ],
                    "encounter" => ["reference" => "urn:uuid:$encounter_uuid"],
                    "performedPeriod" => [
                        "start" => $tglStart,
                        "end" => $tglEnd
                    ],
                    "performer" => [
                        [
                            "actor" => [
                                "reference" => "Practitioner/$practitioner_uuid",
                                "display" => $request->datasimpeg['nama'] ?? '-'
                            ]
                        ]
                    ],
                    "note" => [
                        [
                            "text" => "Prosedur Puasa tidak dilakukan Pasien"
                        ]
                    ]
                ],
                "request" => ["method" => "POST", "url" => "Procedure"]
            ];
        }

        // 2. Tindakan Medis / Terapeutik ICD-9
        $tindakan = $request->tindakan ?? [];
        if (count($tindakan) > 0) {
            foreach ($tindakan as $isi) {
                if ($isi->maapingprocedure !== null && $isi->maapingsnowmed !== null) {
                    $petugas_id = $isi->petugas['satset_uuid'] ?? $practitioner_uuid;
                    $petugas_nama = $isi->petugas['nama'] ?? ($request->datasimpeg['nama'] ?? '-');
                    $dtStart = Carbon::parse($isi->rs3 ?? $tgl_kunjungan)->toIso8601String();
                    $dtEnd = Carbon::parse($isi->rs3 ?? $tgl_kunjungan)->addMinutes(30)->toIso8601String();

                    $procedures[] = [
                        "fullUrl" => "urn:uuid:" . self::generateUuid(),
                        "resource" => [
                            "resourceType" => "Procedure",
                            "status" => "completed",
                            "category" => [
                                "coding" => [
                                    [
                                        "system" => "http://snomed.info/sct",
                                        "code" => "277132007",
                                        "display" => "Therapeutic procedure"
                                    ]
                                ],
                                "text" => "Prosedur Terapetik"
                            ],
                            "code" => [
                                "coding" => array_values(array_filter([
                                    !empty($isi->maapingprocedure['icd9']) ? [
                                        "system" => "http://hl7.org/fhir/sid/icd-9-cm",
                                        "code" => $isi->maapingprocedure['icd9'],
                                        "display" => $isi->maapingprocedure['prosedur'] ?? ($isi->keterangan ?? 'Tindakan Terapeutik')
                                    ] : null,
                                    !empty($isi->maapingsnowmed['kdSnowmed']) ? [
                                        "system" => "http://snomed.info/sct",
                                        "code" => (string)$isi->maapingsnowmed['kdSnowmed'],
                                        "display" => $isi->maapingsnowmed['display'] ?? ($isi->keterangan ?? 'Procedure')
                                    ] : null,
                                ])),
                                "text" => $isi->keterangan ?? 'Tindakan Medis'
                            ],
                            "subject" => [
                                "reference" => "Patient/$pasien_uuid",
                                "display" => $namaPasien
                            ],
                            "encounter" => [
                                "reference" => "urn:uuid:$encounter_uuid",
                                "display" => "Tindakan " . ($isi->keterangan ?? 'Terapeutik') . " $namaPasien"
                            ],
                            "performedPeriod" => [
                                "start" => $dtStart,
                                "end" => $dtEnd
                            ],
                            "performer" => [
                                [
                                    "actor" => [
                                        "reference" => "Practitioner/$petugas_id",
                                        "display" => $petugas_nama
                                    ]
                                ]
                            ],
                            "note" => [
                                [
                                    "text" => "Tindakan: " . ($isi->keterangan ?? 'Tindakan medis rawat inap')
                                ]
                            ]
                        ],
                        "request" => ["method" => "POST", "url" => "Procedure"]
                    ];
                }
            }
        }

        // 3. Prosedur Edukasi (Disease process or condition education)
        $procedures[] = [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "Procedure",
                "status" => "completed",
                "category" => [
                    "coding" => [
                        [
                            "system" => "http://snomed.info/sct",
                            "code" => "409073007",
                            "display" => "Education"
                        ]
                    ],
                    "text" => "Education"
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://snomed.info/sct",
                            "code" => "84635008",
                            "display" => "Disease process or condition education "
                        ]
                    ]
                ],
                "subject" => [
                    "reference" => "Patient/$pasien_uuid",
                    "display" => $namaPasien
                ],
                "encounter" => [
                    "reference" => "urn:uuid:$encounter_uuid",
                    "display" => "Edukasi Proses Penyakit, Diagnosis, dan Rencana Asuhan kepada $namaPasien"
                ],
                "performedPeriod" => [
                    "start" => $tglStart,
                    "end" => $tglEnd
                ],
                "performer" => [
                    [
                        "actor" => [
                            "reference" => "Practitioner/$practitioner_uuid"
                        ]
                    ]
                ],
                "note" => [
                    [
                        "text" => "Edukasi Proses Penyakit, Diagnosis, dan Rencana Asuhan"
                    ]
                ]
            ],
            "request" => ["method" => "POST", "url" => "Procedure"]
        ];

        return $procedures;
    }

    public static function clinicalImpressionRanap($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id, $condPrimerUuid, $diagPrimer)
    {
        $namaPasien = $request->nama ?? $request->nama_panggil ?? 'Pasien';
        $tglEff = Carbon::parse($tgl_kunjungan)->toIso8601String();
        $codeIcd10 = $diagPrimer['kode'] ?? ($request->diagakhir ?? 'Z00.0');
        $displayDiag = $diagPrimer['inggris'] ?? $diagPrimer['indonesia'] ?? ($request->memodiagnosa ?? 'Pemeriksaan Rawat Inap');

        // Mapping Prognosis SNOMED
        $progKode = $request->prognosis ?? '';
        $snomedProg = ["code" => "65872000", "display" => "Fair prognosis"];

        if ($progKode == '1' || $progKode == '7') {
            $snomedProg = ["code" => "67334001", "display" => "Good prognosis"];
        } elseif ($progKode == '2' || $progKode == '8') {
            $snomedProg = ["code" => "65872000", "display" => "Fair prognosis"];
        } elseif ($progKode == '3' || $progKode == '4' || $progKode == '9') {
            $snomedProg = ["code" => "170968001", "display" => "Poor prognosis"];
        } elseif ($progKode == '5' || $progKode == '11') {
            $snomedProg = ["code" => "170969009", "display" => "Very poor prognosis"];
        }

        return [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "ClinicalImpression",
                "identifier" => [
                    [
                        "use" => "official",
                        "system" => "http://sys-ids.kemkes.go.id/clinicalimpression/" . $organization_id,
                        "value" => "PROG-" . ($request->noreg ?? $request->rs1)
                    ]
                ],
                "status" => "completed",
                "description" => "Pasien $namaPasien terdiagnosis " . $displayDiag,
                "subject" => [
                    "reference" => "Patient/$pasien_uuid",
                    "display" => $namaPasien
                ],
                "encounter" => [
                    "reference" => "urn:uuid:$encounter_uuid",
                    "display" => "Kunjungan Rawat Inap $namaPasien"
                ],
                "effectiveDateTime" => $tglEff,
                "date" => $tglEff,
                "assessor" => [
                    "reference" => "Practitioner/$practitioner_uuid"
                ],
                "problem" => [
                    [
                        "reference" => $condPrimerUuid ?: ("urn:uuid:" . self::generateUuid())
                    ]
                ],
                "summary" => "Prognosis " . $displayDiag,
                "finding" => [
                    [
                        "itemCodeableConcept" => [
                            "coding" => [
                                [
                                    "system" => "http://hl7.org/fhir/sid/icd-10",
                                    "code" => $codeIcd10,
                                    "display" => $displayDiag
                                ]
                            ]
                        ],
                        "itemReference" => [
                            "reference" => $condPrimerUuid ?: ("urn:uuid:" . self::generateUuid())
                        ]
                    ]
                ],
                "prognosisCodeableConcept" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => $snomedProg['code'],
                                "display" => $snomedProg['display']
                            ]
                        ]
                    ]
                ]
            ],
            "request" => ["method" => "POST", "url" => "ClinicalImpression"]
        ];
    }

    public static function serviceRequestKontrolRanap($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id, $diagPrimer)
    {
        $namaPasien = $request->nama ?? $request->nama_panggil ?? 'Pasien';
        $tglAuthored = $request->tglkeluar ? Carbon::parse($request->tglkeluar)->toIso8601String() : Carbon::parse($tgl_kunjungan)->toIso8601String();
        $tglKontrol = $request->tglkeluar ? Carbon::parse($request->tglkeluar)->addDays(7)->toIso8601String() : Carbon::parse($tgl_kunjungan)->addDays(7)->toIso8601String();
        $codeIcd10 = $diagPrimer['kode'] ?? ($request->diagakhir ?? 'Z00.0');
        $displayDiag = $diagPrimer['inggris'] ?? $diagPrimer['indonesia'] ?? ($request->memodiagnosa ?? 'Pemeriksaan Rawat Inap');

        return [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "ServiceRequest",
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/servicerequest/" . $organization_id,
                        "value" => "KTR-" . ($request->noreg ?? $request->rs1)
                    ]
                ],
                "status" => "active",
                "intent" => "original-order",
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "3457005",
                                "display" => "Patient referral"
                            ]
                        ]
                    ]
                ],
                "priority" => "routine",
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://snomed.info/sct",
                            "code" => "185389009",
                            "display" => "Follow-up visit"
                        ]
                    ],
                    "text" => "Kontrol 1 minggu Pasca Rawat Inap"
                ],
                "subject" => [
                    "reference" => "Patient/$pasien_uuid"
                ],
                "encounter" => [
                    "reference" => "urn:uuid:$encounter_uuid",
                    "display" => "Kontrol Pasca Rawat Inap $namaPasien"
                ],
                "occurrenceDateTime" => $tglKontrol,
                "authoredOn" => $tglAuthored,
                "requester" => [
                    "reference" => "Practitioner/$practitioner_uuid",
                    "display" => $request->datasimpeg['nama'] ?? '-'
                ],
                "performer" => [
                    [
                        "reference" => "Practitioner/$practitioner_uuid",
                        "display" => $request->datasimpeg['nama'] ?? '-'
                    ]
                ],
                "reasonCode" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://hl7.org/fhir/sid/icd-10",
                                "code" => $codeIcd10,
                                "display" => $displayDiag
                            ]
                        ],
                        "text" => "Kontrol rutin 1 minggu pertama pasca rawat inap"
                    ]
                ],
                "patientInstruction" => "Kontrol rutin 1 minggu pasca rawat inap. Dalam keadaan darurat segera menuju IGD Rumah Sakit."
            ],
            "request" => ["method" => "POST", "url" => "ServiceRequest"]
        ];
    }

    public static function compositionRanap($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id, $condPrimerUuid, $diagPrimer, $conditionEntries = [], $procedureEntries = [])
    {
        $namaPasien = $request->nama ?? $request->nama_panggil ?? 'Pasien';
        $tglPulang = ($request->tglkeluar && $request->tglkeluar != '0000-00-00 00:00:00') ? Carbon::parse($request->tglkeluar)->toIso8601String() : Carbon::parse($tgl_kunjungan)->toIso8601String();
        $displayDiag = $diagPrimer['inggris'] ?? $diagPrimer['indonesia'] ?? ($request->diagakhir ?? 'Pemeriksaan Rawat Inap');

        $sections = [];

        // Section 1: Diagnosis Pulang
        $diagRefs = [];
        if (!empty($condPrimerUuid)) {
            $diagRefs[] = ["reference" => $condPrimerUuid];
        }
        foreach ($conditionEntries as $c) {
            if (!empty($c['fullUrl']) && $c['fullUrl'] !== $condPrimerUuid) {
                $diagRefs[] = ["reference" => $c['fullUrl']];
            }
        }

        if (empty($diagRefs)) {
            $diagRefs[] = ["reference" => "urn:uuid:" . self::generateUuid()];
        }

        $sections[] = [
            "title" => "Diagnosis Akhir / Pulang",
            "code" => [
                "coding" => [
                    [
                        "system" => "http://loinc.org",
                        "code" => "11535-2",
                        "display" => "Hospital discharge Dx"
                    ]
                ]
            ],
            "text" => [
                "status" => "additional",
                "div" => $displayDiag
            ],
            "entry" => $diagRefs
        ];

        // Section 2: Tindakan / Prosedur (jika ada)
        if (!empty($procedureEntries)) {
            $procRefs = [];
            foreach ($procedureEntries as $p) {
                if (!empty($p['fullUrl'])) {
                    $procRefs[] = ["reference" => $p['fullUrl']];
                }
            }
            if (!empty($procRefs)) {
                $sections[] = [
                    "title" => "Tindakan dan Prosedur Medis",
                    "code" => [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "8724-7",
                                "display" => "Surgical operation note description"
                            ]
                        ]
                    ],
                    "text" => [
                        "status" => "additional",
                        "div" => "Tindakan dan asuhan medis selama perawatan rawat inap"
                    ],
                    "entry" => $procRefs
                ];
            }
        }

        return [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "Composition",
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/composition/" . $organization_id,
                        "value" => "RESUME-" . ($request->noreg ?? $request->rs1)
                    ]
                ],
                "status" => "final",
                "type" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "18842-5",
                            "display" => "Discharge summary"
                        ]
                    ]
                ],
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://loinc.org",
                                "code" => "LP173421-1",
                                "display" => "Report"
                            ]
                        ]
                    ]
                ],
                "subject" => [
                    "reference" => "Patient/$pasien_uuid",
                    "display" => $namaPasien
                ],
                "encounter" => [
                    "reference" => "urn:uuid:$encounter_uuid",
                    "display" => "Kunjungan Rawat Inap $namaPasien"
                ],
                "date" => $tglPulang,
                "author" => [
                    [
                        "reference" => "Practitioner/$practitioner_uuid",
                        "display" => $request->datasimpeg['nama'] ?? '-'
                    ]
                ],
                "title" => "Ringkasan Pulang Rawat Inap",
                "custodian" => [
                    "reference" => "Organization/$organization_id"
                ],
                "section" => $sections
            ],
            "request" => ["method" => "POST", "url" => "Composition"]
        ];
    }

    public static function apotekRanap($request, $encounter_uuid, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid, $organization_id, $condPrimerUuid, $diagPrimer)
    {
        $entries = [];
        $resep = $request->apotek ?? [];
        $namaPasien = $request->nama ?? $request->nama_panggil ?? 'Pasien';
        $codeIcd10 = $diagPrimer['kode'] ?? ($request->diagakhir ?? 'Z00.0');
        $displayDiag = $diagPrimer['inggris'] ?? $diagPrimer['indonesia'] ?? ($request->memodiagnosa ?? 'Terapi Pasien Rawat Inap');

        $hasKajianResep = false;

        foreach ($resep as $r) {
            $noresep = $r['noresep'] ?? ($request->noreg ?? 'R-001');
            $tgl_kirim = $r['tgl_kirim'] ? Carbon::parse($r['tgl_kirim'])->toIso8601String() : Carbon::parse($tgl_kunjungan)->toIso8601String();
            $tgl_selesai = $r['tgl_selesai'] ? Carbon::parse($r['tgl_selesai'])->toIso8601String() : $tgl_kirim;

            $apoteker_uuid = $r['petugas']['satset_uuid'] ?? $practitioner_uuid;
            $nama_apoteker = $r['petugas']['nama'] ?? ($request->datasimpeg['nama'] ?? '-');

            // 1. QuestionnaireResponse Kajian Resep Q0007 (1 per resep / bundle)
            if (!$hasKajianResep) {
                $hasKajianResep = true;
                $entries[] = [
                    "fullUrl" => "urn:uuid:" . self::generateUuid(),
                    "resource" => [
                        "resourceType" => "QuestionnaireResponse",
                        "questionnaire" => "https://fhir.kemkes.go.id/Questionnaire/Q0007",
                        "status" => "completed",
                        "subject" => [
                            "reference" => "Patient/$pasien_uuid",
                            "display" => $namaPasien
                        ],
                        "encounter" => [
                            "reference" => "urn:uuid:$encounter_uuid"
                        ],
                        "authored" => $tgl_kirim,
                        "author" => [
                            "reference" => "Practitioner/$apoteker_uuid"
                        ],
                        "source" => [
                            "reference" => "Patient/$pasien_uuid"
                        ],
                        "item" => [
                            [
                                "linkId" => "1",
                                "text" => "Persyaratan Administrasi",
                                "item" => [
                                    [
                                        "linkId" => "1.1",
                                        "text" => "Apakah nama, umur, jenis kelamin, berat badan dan tinggi badan pasien sudah sesuai?",
                                        "answer" => [["valueCoding" => ["system" => "http://terminology.kemkes.go.id/CodeSystem/clinical-term", "code" => "OV000052", "display" => "Sesuai"]]]
                                    ],
                                    [
                                        "linkId" => "1.2",
                                        "text" => "Apakah nama, nomor ijin, alamat dan paraf dokter sudah sesuai?",
                                        "answer" => [["valueCoding" => ["system" => "http://terminology.kemkes.go.id/CodeSystem/clinical-term", "code" => "OV000052", "display" => "Sesuai"]]]
                                    ],
                                    [
                                        "linkId" => "1.3",
                                        "text" => "Apakah tanggal resep sudah sesuai?",
                                        "answer" => [["valueCoding" => ["system" => "http://terminology.kemkes.go.id/CodeSystem/clinical-term", "code" => "OV000052", "display" => "Sesuai"]]]
                                    ],
                                    [
                                        "linkId" => "1.4",
                                        "text" => "Apakah ruangan/unit asal resep sudah sesuai?",
                                        "answer" => [["valueCoding" => ["system" => "http://terminology.kemkes.go.id/CodeSystem/clinical-term", "code" => "OV000052", "display" => "Sesuai"]]]
                                    ]
                                ]
                            ],
                            [
                                "linkId" => "2",
                                "text" => "Persyaratan Farmasetik",
                                "item" => [
                                    [
                                        "linkId" => "2.1",
                                        "text" => "Apakah nama obat, bentuk dan kekuatan sediaan sudah sesuai?",
                                        "answer" => [["valueCoding" => ["system" => "http://terminology.kemkes.go.id/CodeSystem/clinical-term", "code" => "OV000052", "display" => "Sesuai"]]]
                                    ],
                                    [
                                        "linkId" => "2.2",
                                        "text" => "Apakah dosis dan jumlah obat sudah sesuai?",
                                        "answer" => [["valueCoding" => ["system" => "http://terminology.kemkes.go.id/CodeSystem/clinical-term", "code" => "OV000052", "display" => "Sesuai"]]]
                                    ],
                                    [
                                        "linkId" => "2.3",
                                        "text" => "Apakah stabilitas obat sudah sesuai?",
                                        "answer" => [["valueCoding" => ["system" => "http://terminology.kemkes.go.id/CodeSystem/clinical-term", "code" => "OV000052", "display" => "Sesuai"]]]
                                    ],
                                    [
                                        "linkId" => "2.4",
                                        "text" => "Apakah aturan dan cara penggunaan obat sudah sesuai?",
                                        "answer" => [["valueCoding" => ["system" => "http://terminology.kemkes.go.id/CodeSystem/clinical-term", "code" => "OV000052", "display" => "Sesuai"]]]
                                    ]
                                ]
                            ],
                            [
                                "linkId" => "3",
                                "text" => "Persyaratan Klinis",
                                "item" => [
                                    [
                                        "linkId" => "3.1",
                                        "text" => "Apakah ketepatan indikasi, dosis, dan waktu penggunaan obat sudah sesuai?",
                                        "answer" => [["valueCoding" => ["system" => "http://terminology.kemkes.go.id/CodeSystem/clinical-term", "code" => "OV000052", "display" => "Sesuai"]]]
                                    ],
                                    [
                                        "linkId" => "3.2",
                                        "text" => "Apakah terdapat duplikasi pengobatan?",
                                        "answer" => [["valueBoolean" => false]]
                                    ],
                                    [
                                        "linkId" => "3.3",
                                        "text" => "Apakah terdapat alergi dan reaksi obat yang tidak dikehendaki (ROTD)?",
                                        "answer" => [["valueBoolean" => false]]
                                    ],
                                    [
                                        "linkId" => "3.4",
                                        "text" => "Apakah terdapat kontraindikasi pengobatan?",
                                        "answer" => [["valueBoolean" => false]]
                                    ],
                                    [
                                        "linkId" => "3.5",
                                        "text" => "Apakah terdapat dampak interaksi obat?",
                                        "answer" => [["valueBoolean" => false]]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    "request" => ["method" => "POST", "url" => "QuestionnaireResponse"]
                ];
            }

            // 2. Obat Non-Racikan
            $nonRacikan = $r['rincian'] ?? [];
            foreach ($nonRacikan as $itemObat) {
                $kfa = $itemObat['mobat']['kfa'] ?? null;
                $kode_kfa = $itemObat['mobat']['kode_kfa'] ?? null;
                $displayObat = $itemObat['mobat']['kfa']['response']['result']['name'] ?? ($itemObat['mobat']['nama_obat'] ?? 'Obat Rawat Inap');
                $kdobat = $itemObat['kdobat'] ?? 'OBT';
                $idRincian = $itemObat['id'] ?? self::generateUuid();

                if ($kfa && $kode_kfa) {
                    $medReqUuid = "urn:uuid:" . self::generateUuid();
                    $medDispUuid = "urn:uuid:" . self::generateUuid();
                    $medicationReqId = "urn:uuid:" . self::generateUuid();
                    $medicationDispId = "urn:uuid:" . self::generateUuid();

                    $rawQty = $itemObat['qty'] ?? 1;
                    $qty = is_numeric($rawQty) ? (float)$rawQty : 1.0;
                    if ($qty <= 0) {
                        $qty = 1.0;
                    }
                    $aturan = !empty($itemObat['aturan']) ? $itemObat['aturan'] : '3x1';

                    $medReqIdent = Str::random(20);
                    $medDispIdent = Str::random(20);

                    // Medication (for Request)
                    $entries[] = [
                        "fullUrl" => $medicationReqId,
                        "resource" => [
                            "resourceType" => "Medication",
                            "meta" => [
                                "profile" => ["https://fhir.kemkes.go.id/r4/StructureDefinition/Medication"]
                            ],
                            "extension" => [
                                [
                                    "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                                    "valueCodeableConcept" => [
                                        "coding" => [
                                            [
                                                "system" => "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                                "code" => "NC",
                                                "display" => "Non-compound"
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            "identifier" => [
                                [
                                    "use" => "official",
                                    "system" => "http://sys-ids.kemkes.go.id/medication/" . $organization_id,
                                    "value" => $medReqIdent
                                ]
                            ],
                            "code" => [
                                "coding" => [
                                    [
                                        "system" => "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => (string)$kode_kfa,
                                        "display" => $displayObat
                                    ]
                                ]
                            ],
                            "status" => "active"
                        ],
                        "request" => ["method" => "POST", "url" => "Medication"]
                    ];

                    // Medication (for Dispense)
                    $entries[] = [
                        "fullUrl" => $medicationDispId,
                        "resource" => [
                            "resourceType" => "Medication",
                            "meta" => [
                                "profile" => ["https://fhir.kemkes.go.id/r4/StructureDefinition/Medication"]
                            ],
                            "extension" => [
                                [
                                    "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType",
                                    "valueCodeableConcept" => [
                                        "coding" => [
                                            [
                                                "system" => "http://terminology.kemkes.go.id/CodeSystem/medication-type",
                                                "code" => "NC",
                                                "display" => "Non-compound"
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            "identifier" => [
                                [
                                    "use" => "official",
                                    "system" => "http://sys-ids.kemkes.go.id/medication/" . $organization_id,
                                    "value" => $medDispIdent
                                ]
                            ],
                            "code" => [
                                "coding" => [
                                    [
                                        "system" => "http://sys-ids.kemkes.go.id/kfa",
                                        "code" => (string)$kode_kfa,
                                        "display" => $displayObat
                                    ]
                                ]
                            ],
                            "status" => "active",
                            "batch" => [
                                "lotNumber" => "BATCH-" . ($kdobat ?: '1001'),
                                "expirationDate" => date('Y-m-d', strtotime('+2 years'))
                            ]
                        ],
                        "request" => ["method" => "POST", "url" => "Medication"]
                    ];

                    // MedicationRequest Inpatient
                    $entries[] = [
                        "fullUrl" => $medReqUuid,
                        "resource" => [
                            "resourceType" => "MedicationRequest",
                            "identifier" => [
                                [
                                    "use" => "official",
                                    "system" => "http://sys-ids.kemkes.go.id/prescription/" . $organization_id,
                                    "value" => $noresep
                                ],
                                [
                                    "use" => "official",
                                    "system" => "http://sys-ids.kemkes.go.id/prescription-item/" . $organization_id,
                                    "value" => "$noresep-$idRincian"
                                ]
                            ],
                            "status" => "completed",
                            "intent" => "order",
                            "category" => [
                                [
                                    "coding" => [
                                        [
                                            "system" => "http://terminology.hl7.org/CodeSystem/medicationrequest-category",
                                            "code" => "inpatient",
                                            "display" => "Inpatient"
                                        ]
                                    ]
                                ]
                            ],
                            "priority" => "routine",
                            "medicationReference" => [
                                "reference" => $medicationReqId,
                                "display" => $displayObat
                            ],
                            "subject" => [
                                "reference" => "Patient/$pasien_uuid",
                                "display" => $namaPasien
                            ],
                            "encounter" => [
                                "reference" => "urn:uuid:$encounter_uuid"
                            ],
                            "authoredOn" => $tgl_kirim,
                            "requester" => [
                                "reference" => "Practitioner/$practitioner_uuid",
                                "display" => $request->datasimpeg['nama'] ?? '-'
                            ],
                            "reasonReference" => [
                                [
                                    "reference" => $condPrimerUuid ?: ("urn:uuid:" . self::generateUuid()),
                                    "display" => $displayDiag
                                ]
                            ],
                            "dosageInstruction" => [
                                [
                                    "sequence" => 1,
                                    "patientInstruction" => $aturan,
                                    "route" => [
                                        "coding" => [
                                            [
                                                "system" => "http://www.whocc.no/atc",
                                                "code" => "O",
                                                "display" => "Oral"
                                            ]
                                        ]
                                    ],
                                    "doseAndRate" => [
                                        [
                                            "type" => [
                                                "coding" => [
                                                    [
                                                        "system" => "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                        "code" => "ordered",
                                                        "display" => "Ordered"
                                                    ]
                                                ]
                                            ],
                                            "doseQuantity" => [
                                                "value" => 1,
                                                "unit" => "Tab",
                                                "system" => "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                                "code" => "TAB"
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            "dispenseRequest" => [
                                "numberOfRepeatsAllowed" => 0,
                                "quantity" => [
                                    "value" => $qty,
                                    "unit" => "Tab",
                                    "system" => "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                    "code" => "TAB"
                                ],
                                "performer" => [
                                    "reference" => "Organization/" . $organization_id
                                ]
                            ]
                        ],
                        "request" => ["method" => "POST", "url" => "MedicationRequest"]
                    ];

                    // MedicationDispense Inpatient
                    $entries[] = [
                        "fullUrl" => $medDispUuid,
                        "resource" => [
                            "resourceType" => "MedicationDispense",
                            "identifier" => [
                                [
                                    "use" => "official",
                                    "system" => "http://sys-ids.kemkes.go.id/prescription/" . $organization_id,
                                    "value" => $noresep
                                ],
                                [
                                    "use" => "official",
                                    "system" => "http://sys-ids.kemkes.go.id/prescription-item/" . $organization_id,
                                    "value" => "$noresep-$idRincian-DISP"
                                ]
                            ],
                            "status" => "completed",
                            "category" => [
                                "coding" => [
                                    [
                                        "system" => "http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category",
                                        "code" => "inpatient",
                                        "display" => "Inpatient"
                                    ]
                                ]
                            ],
                            "medicationReference" => [
                                "reference" => $medicationDispId,
                                "display" => $displayObat
                            ],
                            "subject" => [
                                "reference" => "Patient/$pasien_uuid",
                                "display" => $namaPasien
                            ],
                            "context" => [
                                "reference" => "urn:uuid:$encounter_uuid"
                            ],
                            "performer" => [
                                [
                                    "actor" => [
                                        "reference" => "Practitioner/$apoteker_uuid"
                                    ]
                                ]
                            ],
                            "authorizingPrescription" => [
                                [
                                    "reference" => $medReqUuid
                                ]
                            ],
                            "quantity" => [
                                "value" => $qty,
                                "unit" => "Tab",
                                "system" => "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                "code" => "TAB"
                            ],
                            "whenPrepared" => $tgl_kirim,
                            "whenHandedOver" => $tgl_selesai,
                            "dosageInstruction" => [
                                [
                                    "sequence" => 1,
                                    "patientInstruction" => $aturan,
                                    "route" => [
                                        "coding" => [
                                            [
                                                "system" => "http://www.whocc.no/atc",
                                                "code" => "O",
                                                "display" => "Oral"
                                            ]
                                        ]
                                    ],
                                    "doseAndRate" => [
                                        [
                                            "type" => [
                                                "coding" => [
                                                    [
                                                        "system" => "http://terminology.hl7.org/CodeSystem/dose-rate-type",
                                                        "code" => "ordered",
                                                        "display" => "Ordered"
                                                    ]
                                                ]
                                            ],
                                            "doseQuantity" => [
                                                "value" => 1,
                                                "unit" => "Tab",
                                                "system" => "http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm",
                                                "code" => "TAB"
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        "request" => ["method" => "POST", "url" => "MedicationDispense"]
                    ];
                }
            }
        }

        return $entries;
    }

    public static function radiologi($request, $pasien_uuid, $encounter_uuid, $organization_id)
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

        $form = [];
        $ruangId = $request->relmasterruangranap->ruang->satset_uuid ?? $request->relmasterruangranap['ruang']['satset_uuid'] ?? null;
        $lantai = $request->relmasterruangranap->ruang->lantai ?? $request->relmasterruangranap['ruang']['lantai'] ?? '-';
        $gedung = $request->relmasterruangranap->ruang->gedung ?? $request->relmasterruangranap['ruang']['gedung'] ?? '-';
        $practitioner_uuid = $request?->datasimpeg?->satset_uuid;

        if ($imunisasi) {
            $form = [
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
                        "display" => $request->nama ?? $request->nama_panggil
                    ],
                    "encounter" => [
                        "reference" => "urn:uuid:" . $encounter_uuid
                    ],
                    "occurrenceDateTime" => Carbon::parse($imunisasi['created_at'])->toIso8601String(),
                    "primarySource" => true,
                    "lotNumber" => $imunisasi['kdobat'] ?? 'LOT-HB0',
                    "expirationDate" => date('Y-m-d', strtotime('+18 months')),
                    "location" => [
                        "reference" => "Location/" . ($ruangId ?: '00000000-0000-0000-0000-000000000000'),
                        "display" => "Bed " . ($request->nomorbed ?? '-') . ", " . ($request->ruangan ?? 'Ruang Rawat Inap') . ", Lantai $lantai $gedung"
                    ],
                    "performer" => [
                        [
                            "function" => [
                                "coding" => [
                                    [
                                        "system" => "http://terminology.hl7.org/CodeSystem/v2-0443",
                                        "code" => "AP",
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
