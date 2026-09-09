<?php

namespace App\Helpers\Satsets;

use App\Helpers\AuthSatsetHelper;
use App\Helpers\BridgingbpjsHelper;
use App\Helpers\BridgingSatsetHelper;
use App\Models\Pasien;
use App\Models\Satset\SatsetErrorRespon;
use App\Models\Satset\SatsetAuditDataLog;
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

    public static function cekKunjungan($tgl = null)
    {
        $query = KunjunganPoli::select(
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
            'rs15.rs46 as noka',
            'rs15.rs16 as tgllahir',
            'rs15.rs17 as kelamin',
            'rs15.rs37 as templahir',
            'rs15.rs4 as alamat',
            'rs15.rs55 as nohp',
            'rs15.kd_propinsi as satset_province',
            'rs15.kd_kota as satset_city',
            'rs15.kd_kec as satset_district',
            'rs15.kd_kel as satset_village',
            'rs17.rs19 as status',
            'rs15.satset_uuid as pasien_uuid',
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())) AS usiatahun')
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2')
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8')
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9')
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14')
            ->where(function ($q) {
                $q->where('rs17.rs8', '=', 'POL014')
                  ->orWhere('rs17.rs1', 'LIKE', '%/X')
                  ->orWhere('rs17.rs1', 'LIKE', '%/x');
            })
            ->where('rs17.rs19', '=', '1')
            ->has('diagnosa');

        if ($tgl) {
            $query->where('rs17.rs3', 'LIKE', '%' . $tgl . '%');
        } else {
            $tglAwal = Carbon::now()->subDays(60)->toDateString() . ' 00:00:00';
            $tglAkhir = Carbon::now()->subDays(1)->toDateString() . ' 23:59:59';
            $query->whereBetween('rs17.rs3', [$tglAwal, $tglAkhir]);
        }

        $data = $query
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
                'triage',
                'penilaiananamnesis' => function ($p) {
                    $p->orderBy('id', 'DESC');
                },
                'planheder' => function ($p) {
                    $p->with(['planranap.ruangranap', 'planrujukan', 'planpulang']);
                },
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
            'rs15.rs46 as noka',
            'rs15.rs16 as tgllahir',
            'rs15.rs17 as kelamin',
            'rs15.rs37 as templahir',
            'rs15.rs4 as alamat',
            'rs15.rs55 as nohp',
            'rs15.kd_propinsi as satset_province',
            'rs15.kd_kota as satset_city',
            'rs15.kd_kec as satset_district',
            'rs15.kd_kel as satset_village',
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
                'triage',
                'penilaiananamnesis' => function ($p) {
                    $p->orderBy('id', 'DESC');
                },
                'planheder' => function ($p) {
                    $p->with(['planranap.ruangranap', 'planrujukan', 'planpulang']);
                },
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

    public static function fetchBpjsPeserta($pasien, $unit = 'igd')
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

    public static function getPasienByNikSatset($pasien)
    {
        $nik = trim((string)($pasien->nik ?? $pasien->rs49 ?? ''));
        $norm = trim((string)($pasien->norm ?? $pasien->rs1 ?? ''));

        // Jika NIK belum valid atau kosong, coba cari di BPJS terlebih dahulu
        if (empty($nik) || strlen($nik) !== 16 || str_starts_with($nik, '8888') || str_starts_with($nik, '9999')) {
            $bpjs = self::fetchBpjsPeserta($pasien);
            if ($bpjs && !empty($bpjs->nik)) {
                $nik = trim((string)$bpjs->nik);
            }
        }

        $token = AuthSatsetHelper::accessToken();
        $params = '/Patient?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;

        $send = BridgingSatsetHelper::get_data($token, $params);

        if ($send['message'] === 'success' && isset($send['data']['uuid'])) {
            if (!empty($norm)) {
                Pasien::where('rs1', $norm)->update(['satset_uuid' => $send['data']['uuid']]);
            } elseif (!empty($nik)) {
                Pasien::where('rs49', $nik)->update(['satset_uuid' => $send['data']['uuid']]);
            }
        }
        return $send;
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
                'jenis' => 'igd',
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

    public static function createPatientSatset($pasien)
    {
        try {
            $token = AuthSatsetHelper::accessToken();

            // 1. Prioritaskan ambil data valid dari BPJS (terintegrasi Dukcapil)
            $bpjs = self::fetchBpjsPeserta($pasien);

            $nik = $bpjs ? trim((string)$bpjs->nik) : trim((string)($pasien->nik ?? $pasien->rs49 ?? ''));
            $norm = trim((string)($pasien->norm ?? $pasien->rs1 ?? ''));
            $tgllahir = $bpjs ? trim((string)$bpjs->tglLahir) : ($pasien->tgllahir ?? $pasien->rs16 ?? null);

            $isBayi = !empty($tgllahir) && Carbon::parse($tgllahir)->diffInYears(now()) < 1;

            $genderRaw = $bpjs ? trim((string)$bpjs->sex) : trim((string)($pasien->kelamin ?? $pasien->rs17 ?? ''));
            $genderLower = strtolower($genderRaw);
            $gender = ($genderLower === 'l' || str_starts_with($genderLower, 'laki') || $genderLower === 'male') ? 'male' : 'female';

            $nama = $bpjs ? trim((string)$bpjs->nama) : (!empty($pasien->nama) ? $pasien->nama : (!empty($pasien->rs2) ? $pasien->rs2 : ($pasien->nama_panggil ?? '-')));
            $alamat = $pasien->alamat ?? $pasien->rs4 ?? ($pasien->alamatbarcode ?? '-');
            $templahir = $pasien->templahir ?? $pasien->rs37 ?? '-';
            $nohp = ($bpjs && !empty($bpjs->mr->noTelepon)) ? trim((string)$bpjs->mr->noTelepon) : ($pasien->nohp ?? $pasien->rs55 ?? '-');


            $rawProv = trim((string)($pasien?->satset_province ?? $pasien?->kd_propinsi ?? '35'));
            $prov = (strlen($rawProv) === 2 && $rawProv !== '00') ? $rawProv : '35';

            $rawCity = trim((string)($pasien?->satset_city ?? $pasien?->kd_kota ?? '74'));
            $cityCode = (strlen($rawCity) >= 4) ? $rawCity : ((strlen($rawCity) > 0 && $rawCity !== '00') ? ($prov . str_pad($rawCity, 2, '0', STR_PAD_LEFT)) : ($prov === '35' ? '3574' : $prov . '01'));

            $rawDist = trim((string)($pasien?->satset_district ?? $pasien?->kd_kec ?? '04'));
            $distCode = (strlen($rawDist) >= 6) ? $rawDist : ((strlen($rawDist) > 0 && $rawDist !== '00') ? ($cityCode . str_pad($rawDist, 2, '0', STR_PAD_LEFT)) : ($cityCode === '3574' ? '357402' : $cityCode . '01'));

            $rawVill = trim((string)($pasien?->satset_village ?? $pasien?->kd_kel ?? '1003'));
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
                        "text" => $nama,
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
                        "city" => !empty($pasien?->satset_city_name) && $pasien?->satset_city_name !== '-' ? $pasien->satset_city_name : "KOTA PROBOLINGGO",
                        "district" => !empty($pasien?->satset_district_name) && $pasien?->satset_district_name !== '-' ? $pasien->satset_district_name : "Kanigaran",
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

    public static function kirimKunjunganIgd($data)
    {
        $pasien_uuid = $data->pasien_uuid;
        if (!$pasien_uuid) {
            $getPasienFromSatset = self::getPasienByNikSatset($data);
            $pasien_uuid = $getPasienFromSatset['data']['uuid'] ?? null;
            if (!$pasien_uuid) {
                $createPasien = self::createPatientSatset($data);
                $pasien_uuid = $createPasien['uuid'] ?? null;
            }
        }

        if (!$pasien_uuid) {
            $err = [
                'method' => 'POST',
                'url' => 'https://api-satusehat.kemkes.go.id/fhir-r4/v1',
                'response' => ['message' => 'Pasien UUID / NIK Tidak Ditemukan di SatuSehat'],
                'uuid' => $data->noreg,
                'jenis' => 'igd',
                'error_summary' => 'Pasien UUID / NIK Tidak Ditemukan di SatuSehat',
            ];
            SatsetErrorRespon::updateOrCreate(['uuid' => $data->noreg], $err);
            return ['message' => 'failed', 'data' => 'Pasien Belum Terkoneksi Ke Satu Sehat'];
        }

        $practitioner = $data->datasimpeg ? ($data->datasimpeg['satset_uuid'] ?? null) : null;
        if (!$practitioner) {
            $getPrac = self::getPractitionerFromSatset($data);
            $practitioner = $getPrac['data']['uuid'] ?? null;
        }

        if (!$practitioner) {
            $err = [
                'method' => 'POST',
                'url' => 'https://api-satusehat.kemkes.go.id/fhir-r4/v1',
                'response' => ['message' => 'Dokter IGD Belum Terkoneksi Ke Satu Sehat'],
                'uuid' => $data->noreg,
                'jenis' => 'igd',
                'error_summary' => 'IHS Dokter IGD Tidak Ditemukan',
            ];
            SatsetErrorRespon::updateOrCreate(['uuid' => $data->noreg], $err);
            return ['message' => 'failed', 'data' => 'Dokter IGD Belum Terkoneksi Ke Satu Sehat'];
        }

        // Validasi Diagnosa: JANGAN KIRIM jika diagnosa di SIMRS belum diisi
        if (empty($data->diagnosa) || count($data->diagnosa) === 0) {
            $err = [
                'method' => 'POST',
                'url' => 'https://api-satusehat.kemkes.go.id/fhir-r4/v1',
                'response' => ['message' => 'Diagnosa Dokter Belum Diisi di SIMRS (Pengiriman Dibatalkan)'],
                'uuid' => $data->noreg,
                'jenis' => 'igd',
                'error_summary' => 'Diagnosa Dokter Belum Diisi di SIMRS',
            ];
            SatsetErrorRespon::updateOrCreate(['uuid' => $data->noreg], $err);
            return ['message' => 'failed', 'data' => 'Diagnosa Dokter Belum Diisi di SIMRS'];
        }

        $send = self::form($data, $pasien_uuid, $practitioner);
        if ($send['message'] === 'success') {
            $token = AuthSatsetHelper::accessToken();
            $send = BridgingSatsetHelper::post_bundle($token, $send['data'], $data->noreg, 'igd');
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
        $tglBase = Carbon::parse($tgl_kunjungan);
        $parseTime = function ($val, $fallback) {
            if (empty($val)) return Carbon::parse($fallback);
            if (is_numeric($val)) {
                return strlen((string)$val) >= 13 ? Carbon::createFromTimestampMs((int)$val) : Carbon::createFromTimestamp((int)$val);
            }
            return Carbon::parse($val);
        };

        $antri = count($waktu) > 0 ? $parseTime($waktu[0]->waktu ?? null, $tglBase)->toIso8601String() : $tglBase->toIso8601String();
        $triase_start = count($waktu) > 0 ? $parseTime($waktu[0]->waktu ?? null, $tglBase)->addMinute()->toIso8601String() : $tglBase->copy()->addMinute()->toIso8601String();
        $start = count($waktu) > 1 ? $parseTime($waktu[1]->waktu ?? null, $tglBase)->toIso8601String() : $tglBase->copy()->addMinutes(10)->toIso8601String();
        $end = count($waktu) > 4 ? $parseTime($waktu[4]->waktu ?? null, $tglBase)->toIso8601String() : $tglBase->copy()->addHours(2)->toIso8601String();

        if ($antri > $triase_start) {
            $triase_start = $antri;
        }
        if ($triase_start > $start) {
            $start = $triase_start;
        }
        if ($start > $end) {
            $end = $start;
        }

        // 2. Ruangan IGD
        $relmasterRuang = $request->relmpoli ? $request->relmpoli['ruang'] : null;
        $ruangId = !$relmasterRuang ? '00000000-0000-0000-0000-000000000000' : ($relmasterRuang['satset_uuid'] ?? '00000000-0000-0000-0000-000000000000');
        $ruang = !$relmasterRuang ? 'IGD' : ($relmasterRuang['ruang'] ?? 'IGD');
        $lantai = !$relmasterRuang ? '1' : ($relmasterRuang['lantai'] ?? '1');
        $gedung = !$relmasterRuang ? 'Utama' : ($relmasterRuang['gedung'] ?? 'Utama');

        // Location ServiceClass extension
        $locationExtension = [
            [
                "url" => "https://fhir.kemkes.go.id/r4/StructureDefinition/ServiceClass",
                "extension" => [
                    [
                        "url" => "value",
                        "valueCodeableConcept" => [
                            "coding" => [
                                [
                                    "system" => "http://terminology.kemkes.go.id/CodeSystem/locationServiceClass-Outpatient",
                                    "code" => "reguler",
                                    "display" => "Kelas Reguler"
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
        ];

        // 3. Diagnosis & Condition
        $diagnosa_entries = [];
        $condition_entries = [];
        $conds = $request->diagnosa ?? [];
        $condAwalUuid = null;

        foreach ($conds as $key => $d) {
            $cond_uuid = self::generateUuid();
            if ($key === 0) {
                $condAwalUuid = $cond_uuid;
            }
            $isPrimer = ($d['rs4'] === 'Primer' || $key === 0);
            $diagName = $d['masterdiagnosa'] ? ($d['masterdiagnosa']['rs4'] ?? $d['masterdiagnosa']['rs3'] ?? 'Diagnosis') : 'Diagnosis';

            $diagnosa_entries[] = [
                "condition" => [
                    "reference" => "urn:uuid:$cond_uuid",
                    "display" => $diagName,
                ],
                "use" => [
                    "coding" => [
                        [
                            "system" => "http://terminology.hl7.org/CodeSystem/diagnosis-role",
                            "code" => $isPrimer ? 'AD' : 'DD',
                            "display" => $isPrimer ? 'Admission diagnosis' : 'Discharge diagnosis',
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
                                "display" => $diagName,
                            ]
                        ]
                    ],
                    "subject" => ["reference" => "Patient/$pasien_uuid", "display" => $request->nama],
                    "encounter" => ["reference" => "urn:uuid:$encounter"],
                    "onsetDateTime" => $start,
                    "recordedDate" => $start,
                    "recorder" => ["reference" => "Practitioner/$practitioner"],
                    "note" => [
                        [
                            "text" => "Diagnosis pasien " . $request->nama . ": " . $diagName
                        ]
                    ]
                ],
                "request" => ["method" => "POST", "url" => "Condition"],
            ];
        }

        if (empty($diagnosa_entries)) {
            return ['message' => 'failed', 'data' => 'Diagnosa Pasien IGD Belum Terisi'];
        }

        // 4. Hospitalization / Discharge Disposition
        $hasRanapPlan = !empty($request->planning[0]['spri']) || !empty($request->planning[0]['ranap']);
        $dischargeCode = $hasRanapPlan ? 'oth' : 'home';
        $dischargeDisplay = $hasRanapPlan ? 'Other' : 'Home';
        $dischargeText = $hasRanapPlan ? 'Pasien dipindahkan dari IGD ke rawat inap.' : 'Pasien dipulangkan dari Instalasi Gawat Darurat.';

        $hospitalization = [
            "dischargeDisposition" => [
                "coding" => [
                    [
                        "system" => "http://terminology.hl7.org/CodeSystem/discharge-disposition",
                        "code" => $dischargeCode,
                        "display" => $dischargeDisplay
                    ]
                ],
                "text" => $dischargeText
            ]
        ];

        // 5. Encounter
        $encounter_resource = [
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
                    ],
                    "period" => [
                        "start" => $antri,
                        "end" => $end
                    ],
                    "extension" => $locationExtension
                ]
            ],
            "diagnosis" => $diagnosa_entries,
            "statusHistory" => [
                [
                    "status" => "arrived",
                    "period" => ["start" => $antri, "end" => $triase_start]
                ],
                [
                    "status" => "triaged",
                    "period" => ["start" => $triase_start, "end" => $start]
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
            "hospitalization" => $hospitalization,
            "serviceProvider" => ["reference" => "Organization/$organization_id"],
        ];

        $body = [
            "resourceType" => "Bundle",
            "type" => "transaction",
            "entry" => [
                [
                    "fullUrl" => "urn:uuid:$encounter",
                    "resource" => $encounter_resource,
                    "request" => ["method" => "POST", "url" => "Encounter"]
                ]
            ]
        ];

        // Push Condition
        foreach ($condition_entries as $cond) {
            $body['entry'][] = $cond;
        }

        // Sub-resources
        $refference = [];
        $anamnesis = PostKunjunganRajalHelper::anamnesis($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);
        $observation = self::observationIgd($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);
        $carePlan = self::carePlanIgd($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);
        $procedure = self::procedureIgd($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);
        $plann = PostKunjunganRajalHelper::planning($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id, $refference);
        $alergyIntoleran = PostKunjunganRajalHelper::allergyIntoleran($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id);
        $apotek = PostKunjunganRajalHelper::apotek($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id);
        $laborats = PostKunjunganRajalHelper::laborats($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id, $specimenSnomeds);
        $radiologis = PostKunjunganRajalHelper::radiologi($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id);
        $telaah = PostKunjunganRajalHelper::telaah($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid);

        // Push Anamnesis
        if (!empty($anamnesis['keluhanUtama'])) {
            $body['entry'][] = $anamnesis['keluhanUtama'];
        }

        // Push Observation (TTV, Kesadaran, Risiko Jatuh, Rencana Pulang)
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

        // Push Procedure (Tindakan Emergensi, Pra-Lab, Pra-Rad)
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

        // Push Telaah Resep (QuestionnaireResponse)
        if (!empty($telaah)) {
            $body['entry'][] = $telaah;
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

        // Push Composition (Ringkasan Pelayanan IGD)
        $composition = self::compositionIgd($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id, $condition_entries ?? [], $procedure ?? []);
        if (!empty($composition)) {
            $body['entry'][] = $composition;
        }

        $send['message'] = 'success';
        $send['data'] = $body;

        return $send;
    }

    public static function compositionIgd($request, $encounter, $tgl_kunjungan, $practitioner, $pasien_uuid, $organization_id, $condition_entries = [], $procedure_entries = [])
    {
        $namaPasien = $request->nama ?? $request->nama_panggil ?? 'Pasien';
        $tglKirim = Carbon::parse($tgl_kunjungan)->toIso8601String();

        $sections = [];

        // Section 1: Diagnosis
        $diagRefs = [];
        foreach ($condition_entries as $c) {
            if (!empty($c['fullUrl'])) {
                $diagRefs[] = ["reference" => $c['fullUrl']];
            }
        }
        if (!empty($diagRefs)) {
            $sections[] = [
                "title" => "Diagnosis Instalasi Gawat Darurat",
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
                    "div" => "Diagnosis Pelayanan Gawat Darurat"
                ],
                "entry" => $diagRefs
            ];
        }

        // Section 2: Tindakan Emergensi
        if (!empty($procedure_entries)) {
            $procRefs = [];
            foreach ($procedure_entries as $p) {
                if (!empty($p['fullUrl'])) {
                    $procRefs[] = ["reference" => $p['fullUrl']];
                }
            }
            if (!empty($procRefs)) {
                $sections[] = [
                    "title" => "Tindakan dan Asuhan Kegawatdaruratan",
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
                        "div" => "Tindakan medis dan stabilisasi di IGD"
                    ],
                    "entry" => $procRefs
                ];
            }
        }

        if (empty($sections)) {
            return null;
        }

        return [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "Composition",
                "identifier" => [
                    [
                        "system" => "http://sys-ids.kemkes.go.id/composition/" . $organization_id,
                        "value" => "RESUME-IGD-" . ($request->noreg ?? $request->rs1)
                    ]
                ],
                "status" => "final",
                "type" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "34133-9",
                            "display" => "Summary of episode note"
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
                    "reference" => "urn:uuid:$encounter",
                    "display" => "Pelayanan IGD $namaPasien"
                ],
                "date" => $tglKirim,
                "author" => [
                    [
                        "reference" => "Practitioner/$practitioner",
                        "display" => $request->datasimpeg['nama'] ?? '-'
                    ]
                ],
                "title" => "Ringkasan Pelayanan Gawat Darurat (IGD)",
                "custodian" => [
                    "reference" => "Organization/$organization_id"
                ],
                "section" => $sections
            ],
            "request" => ["method" => "POST", "url" => "Composition"]
        ];
    }

    public static function observationIgd($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid)
    {
        $nama_practitioner = $request->datasimpeg ? $request->datasimpeg['nama'] : '-';
        $organization_id = BridgingSatsetHelper::organization_id();
        $tglEff = Carbon::parse($tgl_kunjungan)->toIso8601String();

        // 1. Ambil data TTV (Prioritaskan dari Triage IGD -> fallback ke Pemeriksaan Fisik)
        $triage = count($request->triage ?? []) > 0 ? $request->triage[0] : null;
        $fisik = count($request->pemeriksaanfisik ?? []) > 0 ? $request->pemeriksaanfisik[0] : null;

        $nadi = $triage ? (int)($triage->rs11 ?? 0) : ($fisik ? (int)($fisik['rs4'] ?? 0) : 0);
        $pernapasan = $triage ? (int)($triage->rs10 ?? 0) : ($fisik ? (int)($fisik['pernapasan'] ?? 0) : 0);
        $sistole = $triage ? (int)($triage->sistole ?? 0) : ($fisik ? (int)($fisik['sistole'] ?? 0) : 0);
        $diastole = $triage ? (int)($triage->diastole ?? 0) : ($fisik ? (int)($fisik['diastole'] ?? 0) : 0);
        $suhu = $triage ? (float)($triage->rs8 ?? 0) : ($fisik ? (float)($fisik['suhutubuh'] ?? 0) : 0);

        // Kesadaran SNOMED
        $kesadaranRaw = $triage ? ($triage->kesadarans ?? '') : ($fisik ? ($fisik['tingkatkesadaran'] ?? '') : '');
        $snowmedKesadaran = [
            "kode" => "248234008",
            "display" => "Mentally alert"
        ];
        $kesadaranLower = strtolower((string)$kesadaranRaw);
        if (str_contains($kesadaranLower, 'voice') || $kesadaranRaw === '1') {
            $snowmedKesadaran = ["kode" => "300202002", "display" => "Response to voice"];
        } elseif (str_contains($kesadaranLower, 'pain') || $kesadaranRaw === '2') {
            $snowmedKesadaran = ["kode" => "450847001", "display" => "Responds to pain"];
        } elseif (str_contains($kesadaranLower, 'unresponsive') || str_contains($kesadaranLower, 'koma') || $kesadaranRaw === '3') {
            $snowmedKesadaran = ["kode" => "422768004", "display" => "Unresponsive"];
        } elseif (str_contains($kesadaranLower, 'delirium') || $kesadaranRaw === '5') {
            $snowmedKesadaran = ["kode" => "2776000", "display" => "Delirium"];
        }

        $formNadi = [
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
                                "display" => "Vital Signs",
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "8867-4",
                            "display" => "Heart rate",
                        ]
                    ]
                ],
                "subject" => ["reference" => "Patient/$pasien_uuid", "display" => $request->nama],
                "encounter" => ["reference" => "urn:uuid:$encounter"],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [["reference" => "Practitioner/$practitioner_uuid", "display" => $nama_practitioner]],
                "valueQuantity" => [
                    "value" => $nadi,
                    "unit" => "/min",
                    "system" => "http://unitsofmeasure.org",
                    "code" => "/min",
                ]
            ],
            "request" => ["method" => "POST", "url" => "Observation"]
        ];

        $formPernapasan = [
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
                                "display" => "Vital Signs",
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "9279-1",
                            "display" => "Respiratory rate",
                        ]
                    ]
                ],
                "subject" => ["reference" => "Patient/$pasien_uuid", "display" => $request->nama],
                "encounter" => ["reference" => "urn:uuid:$encounter"],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [["reference" => "Practitioner/$practitioner_uuid", "display" => $nama_practitioner]],
                "valueQuantity" => [
                    "value" => $pernapasan,
                    "unit" => "/min",
                    "system" => "http://unitsofmeasure.org",
                    "code" => "/min",
                ]
            ],
            "request" => ["method" => "POST", "url" => "Observation"]
        ];

        $formSistole = [
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
                                "display" => "Vital Signs",
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "8480-6",
                            "display" => "Systolic blood pressure",
                        ]
                    ]
                ],
                "subject" => ["reference" => "Patient/$pasien_uuid", "display" => $request->nama],
                "encounter" => ["reference" => "urn:uuid:$encounter"],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [["reference" => "Practitioner/$practitioner_uuid", "display" => $nama_practitioner]],
                "valueQuantity" => [
                    "value" => $sistole,
                    "unit" => "mm[Hg]",
                    "system" => "http://unitsofmeasure.org",
                    "code" => "mm[Hg]",
                ]
            ],
            "request" => ["method" => "POST", "url" => "Observation"]
        ];

        $formDiastole = [
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
                                "display" => "Vital Signs",
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "8462-4",
                            "display" => "Diastolic blood pressure",
                        ]
                    ]
                ],
                "subject" => ["reference" => "Patient/$pasien_uuid", "display" => $request->nama],
                "encounter" => ["reference" => "urn:uuid:$encounter"],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [["reference" => "Practitioner/$practitioner_uuid", "display" => $nama_practitioner]],
                "valueQuantity" => [
                    "value" => $diastole,
                    "unit" => "mm[Hg]",
                    "system" => "http://unitsofmeasure.org",
                    "code" => "mm[Hg]",
                ]
            ],
            "request" => ["method" => "POST", "url" => "Observation"]
        ];

        $formSuhu = [
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
                                "display" => "Vital Signs",
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "8310-5",
                            "display" => "Body temperature",
                        ]
                    ]
                ],
                "subject" => ["reference" => "Patient/$pasien_uuid", "display" => $request->nama],
                "encounter" => ["reference" => "urn:uuid:$encounter"],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [["reference" => "Practitioner/$practitioner_uuid", "display" => $nama_practitioner]],
                "valueQuantity" => [
                    "value" => $suhu,
                    "unit" => "C",
                    "system" => "http://unitsofmeasure.org",
                    "code" => "Cel"
                ]
            ],
            "request" => ["method" => "POST", "url" => "Observation"]
        ];

        $formKesadaran = [
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
                                "display" => "Exam",
                            ]
                        ]
                    ]
                ],
                "code" => [
                    "coding" => [
                        [
                            "system" => "http://loinc.org",
                            "code" => "67775-7",
                            "display" => "Level of responsiveness",
                        ]
                    ]
                ],
                "subject" => ["reference" => "Patient/$pasien_uuid", "display" => $request->nama],
                "encounter" => ["reference" => "urn:uuid:$encounter"],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [["reference" => "Practitioner/$practitioner_uuid", "display" => $nama_practitioner]],
                "valueCodeableConcept" => [
                    "coding" => [
                        [
                            "system" => "http://snomed.info/sct",
                            "code" => $snowmedKesadaran['kode'],
                            "display" => $snowmedKesadaran['display'],
                        ]
                    ]
                ]
            ],
            "request" => ["method" => "POST", "url" => "Observation"]
        ];

        // 2. Observation Risiko Jatuh (Multi-Scale: Morse Fall / Ontario / Humpty Dumpty / Edmonson)
        $fallScore = 0;
        $fallScaleLoinc = "59461-4";
        $fallScaleDisplay = "Fall risk level [Morse Fall Scale]";
        $interpretationCode = 'OI000026';
        $interpretationText = '0 - 24 (Risiko rendah)';

        // Cek data dari Penilaian Anamnesis IGD
        if (count($request->penilaiananamnesis ?? []) > 0) {
            $penilaian = $request->penilaiananamnesis[0];
            $morseJson = is_string($penilaian->morse_fall) ? json_decode($penilaian->morse_fall, true) : $penilaian->morse_fall;
            $ontarioJson = is_string($penilaian->ontario) ? json_decode($penilaian->ontario, true) : $penilaian->ontario;
            $humptyJson = is_string($penilaian->humpty_dumpty) ? json_decode($penilaian->humpty_dumpty, true) : $penilaian->humpty_dumpty;
            $edmonsonJson = is_string($penilaian->edmonson) ? json_decode($penilaian->edmonson, true) : $penilaian->edmonson;

            if (!empty($ontarioJson) && (isset($ontarioJson['skorOntario']['skor']) || isset($ontarioJson['skor']))) {
                // Skala Ontario (Geriatrik)
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
                // Skala Humpty Dumpty (Pediatrik)
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
                // Skala Morse Fall (Dewasa)
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
        } elseif ($fisik && isset($fisik['risikojatuh'])) {
            $fallScore = (int)$fisik['risikojatuh'];
            if ($fallScore >= 45) {
                $interpretationCode = 'OI000028';
                $interpretationText = '>= 45 (Risiko tinggi)';
            } elseif ($fallScore >= 25) {
                $interpretationCode = 'OI000027';
                $interpretationText = '25 - 44 (Risiko sedang)';
            }
        }

        $formRisikoJatuh = [
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
                "subject" => [
                    "reference" => "Patient/$pasien_uuid",
                    "display" => $request->nama
                ],
                "encounter" => ["reference" => "urn:uuid:$encounter"],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [
                    [
                        "reference" => "Practitioner/$practitioner_uuid"
                    ]
                ],
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

        // 3. Observation Kriteria Rencana Pemulangan
        $formRencanaPulang = [
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
                "subject" => [
                    "reference" => "Patient/$pasien_uuid"
                ],
                "encounter" => ["reference" => "urn:uuid:$encounter"],
                "effectiveDateTime" => $tglEff,
                "issued" => $tglEff,
                "performer" => [
                    [
                        "reference" => "Practitioner/$practitioner_uuid"
                    ]
                ],
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

        return [
            'nadi' => $formNadi,
            'pernapasan' => $formPernapasan,
            'sistole' => $formSistole,
            'diastole' => $formDiastole,
            'suhu' => $formSuhu,
            'kesadaran' => $formKesadaran,
            'risikoJatuh' => $formRisikoJatuh,
            'rencanaPulang' => $formRencanaPulang,
        ];
    }

    public static function carePlanIgd($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid)
    {
        $carePlansRaw = PostKunjunganRajalHelper::carePlan($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid);
        $carePlans = [];

        if (is_array($carePlansRaw)) {
            foreach ($carePlansRaw as $cp) {
                if (!empty($cp)) {
                    $authRef = $cp['resource']['author']['reference'] ?? '';
                    if (empty($authRef) || $authRef === 'Practitioner/' || $authRef === 'Practitioner/null' || $authRef === 'Practitioner/-') {
                        $cp['resource']['author'] = [
                            "reference" => "Practitioner/$practitioner_uuid",
                            "display" => $request->datasimpeg['nama'] ?? '-'
                        ];
                    }
                    $carePlans[] = $cp;
                }
            }
        }

        $tglCreated = Carbon::parse($tgl_kunjungan)->addMinutes(15)->toIso8601String();

        // 1. CarePlan Rencana Rawat IGD (Emergency health care plan agreed)
        $descRawat = "Rencana rawat IGD: observasi dan penanganan kegawatdaruratan, tindakan stabilisasi, pemeriksaan penunjang diagnostik, dan tata laksana medis berkelanjutan.";
        if (count($request->pemeriksaanfisik) > 0 && !empty($request->pemeriksaanfisik[0]['planning'])) {
            $descRawat = $request->pemeriksaanfisik[0]['planning'];
        }

        $carePlanRencanaRawat = [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "CarePlan",
                "title" => "Rencana Rawat",
                "status" => "active",
                "intent" => "plan",
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "702779007",
                                "display" => "Emergency health care plan agreed"
                            ]
                        ]
                    ]
                ],
                "description" => $descRawat,
                "subject" => [
                    "reference" => "Patient/$pasien_uuid",
                    "display" => $request->nama
                ],
                "encounter" => ["reference" => "urn:uuid:$encounter"],
                "created" => $tglCreated,
                "author" => [
                    "reference" => "Practitioner/$practitioner_uuid"
                ]
            ],
            "request" => ["method" => "POST", "url" => "CarePlan"]
        ];

        // 2. CarePlan Instruksi Medik dan Keperawatan
        $descInstruksi = "Instruksi medik dan keperawatan: monitoring tanda-tanda vital secara berkala, pemberian terapi cairan dan medikasi emergensi sesuai advis DPJP.";
        if (count($request->pemeriksaanfisik) > 0 && !empty($request->pemeriksaanfisik[0]['instruksidokter'])) {
            $descInstruksi = $request->pemeriksaanfisik[0]['instruksidokter'];
        }

        $carePlanInstruksi = [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "CarePlan",
                "title" => "Instruksi Medik dan Keperawatan",
                "status" => "active",
                "intent" => "plan",
                "category" => [
                    [
                        "coding" => [
                            [
                                "system" => "http://snomed.info/sct",
                                "code" => "702779007",
                                "display" => "Emergency health care plan agreed"
                            ]
                        ]
                    ]
                ],
                "description" => $descInstruksi,
                "subject" => [
                    "reference" => "Patient/$pasien_uuid",
                    "display" => $request->nama
                ],
                "encounter" => ["reference" => "urn:uuid:$encounter"],
                "created" => $tglCreated,
                "author" => [
                    "reference" => "Practitioner/$practitioner_uuid"
                ]
            ],
            "request" => ["method" => "POST", "url" => "CarePlan"]
        ];

        // 3. CarePlan Perencanaan Pemulangan Pasien (Discharge care plan)
        $descPulang = "Rencana pemulangan pasien: kontrol kembali ke fasilitas pelayanan kesehatan / dokter spesialis sesuai jadwal.";
        if (!empty($request->planning[0]['spri'])) {
            $descPulang = "Perawatan lanjutan dipindahkan ke rawat inap.";
        } elseif (!empty($request->planning[0]['kontrol'])) {
            $descPulang = "Kontrol ulang sesuai rencana pada surat kontrol.";
        }

        $carePlanDischarge = [
            "fullUrl" => "urn:uuid:" . self::generateUuid(),
            "resource" => [
                "resourceType" => "CarePlan",
                "title" => "Perencanaan Pemulangan Pasien",
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
                "description" => $descPulang,
                "subject" => [
                    "reference" => "Patient/$pasien_uuid",
                    "display" => $request->nama
                ],
                "encounter" => ["reference" => "urn:uuid:$encounter"],
                "created" => $tglCreated,
                "author" => [
                    "reference" => "Practitioner/$practitioner_uuid"
                ]
            ],
            "request" => ["method" => "POST", "url" => "CarePlan"]
        ];

        $results = is_array($carePlans) ? $carePlans : [];
        $results[] = $carePlanRencanaRawat;
        $results[] = $carePlanInstruksi;
        $results[] = $carePlanDischarge;

        return $results;
    }

    public static function procedureIgd($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid)
    {
        $procedures = PostKunjunganRajalHelper::procedure($request, $encounter, $tgl_kunjungan, $practitioner_uuid, $pasien_uuid);
        $results = is_array($procedures) ? $procedures : [];

        $tglStart = Carbon::parse($tgl_kunjungan)->addMinutes(10)->toIso8601String();
        $tglEnd = Carbon::parse($tgl_kunjungan)->addMinutes(10)->toIso8601String();

        // Prosedur Pra-Lab / Pra-Rad (Fasting / Non-fasting)
        $hasLab = count($request->laborats ?? []) > 0;
        $hasRad = count($request->radiologi ?? []) > 0;

        if ($hasLab || $hasRad) {
            $praProc = [
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
                        "display" => $request->nama
                    ],
                    "encounter" => ["reference" => "urn:uuid:$encounter"],
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
                    ]
                ],
                "request" => ["method" => "POST", "url" => "Procedure"]
            ];

            $results[] = $praProc;
        }

        return $results;
    }
}

