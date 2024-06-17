<?php

namespace App\Http\Controllers\Api\Siasik\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Siasik\Master\Akun_Kepmendg50;
use App\Models\Siasik\Master\Mapping_Bidang_Ptk_Kegiatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\AssignOp\Concat;

class LRAController extends Controller
{
    public function bidang(){
        $thn= date('Y');
        $bidang=Mapping_Bidang_Ptk_Kegiatan::where('tahun', $thn)
        ->select('kodebidang', 'bidang', 'kodekegiatan', 'kegiatan')
        ->groupBy('kodekegiatan')
        ->get();

        return new JsonResponse($bidang);

    }

    public function laplra(){
        $awal=request('tgl', date('Y').'-'.'01-01');
        $akhir=request('tglx');
        $thn= date('Y');

        $kode = Akun50_2024::select('akun50_2024.kodeall2',
        'akun50_2024.uraian', 'akun50_2024.kodeall3'
        )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 5) as kode5'))
        // ->leftJoin('akun50_2024 as wew', DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2)'),'=','wew.kodeall3')
        ->with('kode1',function($gg){
            $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
            })
            ->with('kode2',function($gg){
                $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                })
                ->with('kode3',function($gg){
                    $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                    })
                    ->with('kode4',function($gg){
                        $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                        })
                        ->with('kode5',function($gg){
                            $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                            })
        ->join('t_tampung', 't_tampung.koderek50', '=', 'akun50_2024.kodeall2')
        ->where('tgl', $thn)
        ->when(request('bidang'),function($x) {
            $x->where('t_tampung.bidang', request('bidang'));
        })->when(request('kegiatan'),function($y) {
            $y->where('t_tampung.bidang', request('bidang'))
            ->where('t_tampung.kodekegiatanblud', request('kegiatan'));
        })
        ->with(['anggaran' => function($tgl) use ($thn) {
            $tgl->where('tgl', $thn)
            ->select('t_tampung.koderek50',
                    't_tampung.pagu',
                    't_tampung.kodekegiatanblud',
                    't_tampung.bidang',
                    't_tampung.tgl')
            ->when(request('bidang'),function($x) {
                $x->where('t_tampung.bidang', request('bidang'));
            })->when(request('kegiatan'),function($y) {
                $y->where('t_tampung.bidang', request('bidang'))
                ->where('t_tampung.kodekegiatanblud', request('kegiatan'));
            });
        },'npdls_rinci' => function ($head) use ($awal, $akhir){
            $head->select('npdls_rinci.nonpdls',
                        'npdls_rinci.koderek50',
                        'npdls_rinci.rincianbelanja',
                        'npdls_rinci.nominalpembayaran')
                        // ->groupBy('npdls_rinci.koderek50')
            ->join('npdls_heder', 'npdls_heder.nonpdls', '=', 'npdls_rinci.nonpdls')
            ->join('npkls_rinci', 'npkls_rinci.nonpdls', '=', 'npdls_heder.nonpdls')
            ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
            ->where('npdls_heder.nopencairan', '!=', '')
                ->when(request('bidang'),function($x) {
                    $x->where('npdls_heder.kodebidang', request('bidang'));
                })->when(request('kegiatan'),function($y) {
                    $y->where('npdls_heder.kodebidang', request('bidang'))
                    ->where('npdls_heder.kodekegiatanblud', request('kegiatan'));
                })
                ->whereBetween('npkls_heder.tglpencairan', [$awal, $akhir])

            ->with('headerls',function($npk) use ($awal, $akhir) {
                $npk->select('npdls_heder.nonpdls',
                            'npdls_heder.tglnpdls',
                            'npdls_heder.nopencairan',
                            'npdls_heder.nonpk',
                            'npdls_heder.kodekegiatanblud',
                            'npdls_heder.kodebidang')
                ->join('npkls_rinci', 'npkls_rinci.nonpdls', '=', 'npdls_heder.nonpdls')
                ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
                ->whereBetween('npkls_heder.tglpencairan', [$awal, $akhir])
                ->with('npkrinci',function($header) use ($awal, $akhir) {
                    $header->select('npkls_rinci.nonpk','npkls_rinci.nonpdls')
                    ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
                    ->whereBetween('npkls_heder.tglpencairan', [$awal, $akhir])
                    ->with('header',function($kd){
                        $kd->select('npkls_heder.tglpencairan','npkls_heder.nonpk');
                    });
                });
            });
        },'spjpanjar'=>function($head) use ($awal,$akhir){
            $head->select('spjpanjar_rinci.nospjpanjar',
                        'spjpanjar_rinci.koderek50',
                        'spjpanjar_rinci.rincianbelanja50',
                        'spjpanjar_rinci.sisapanjar',
                        'spjpanjar_rinci.jumlahbelanjapanjar')
            ->join('spjpanjar_heder','spjpanjar_heder.nospjpanjar', '=', 'spjpanjar_rinci.nospjpanjar')
            ->whereBetween('spjpanjar_heder.tglspjpanjar', [$awal, $akhir])
            ->with('spjheader', function($nota){
                $nota->select('spjpanjar_heder.nospjpanjar',
                            'spjpanjar_heder.notapanjar',
                            'spjpanjar_heder.tglspjpanjar',
                            'spjpanjar_heder.kegiatanblud',
                            'spjpanjar_heder.kodekegiatanblud',
                            'spjpanjar_heder.kegiatan')
                ->join('notapanjar_heder', 'notapanjar_heder.nonotapanjar', '=', 'spjpanjar_heder.notapanjar')
                ->when(request('bidang'),function($x) {
                    $x->where('notapanjar_heder.kodebidang', request('bidang'));
                })->when(request('kegiatan'),function($y) {
                    $y->where('notapanjar_heder.kodebidang', request('bidang'))
                    ->where('notapanjar_heder.kodekegiatanblud', request('kegiatan'));
                })->with('nota', function($kd){
                    $kd->select('notapanjar_heder.kodebidang',
                            'notapanjar_heder.kodekegiatanblud',
                            'notapanjar_heder.nonotapanjar');
                });
            });
        },'cp' => function($tgl) use ($awal, $akhir){
            $tgl->select('contrapost.nocontrapost',
                        'contrapost.tglcontrapost',
                        'contrapost.kodekegiatanblud',
                        'contrapost.kegiatanblud',
                        'contrapost.koderek50',
                        'contrapost.rincianbelanja',
                        'contrapost.nominalcontrapost')
            ->join('mappingpptkkegiatan', 'mappingpptkkegiatan.kodekegiatan', '=', 'contrapost.kodekegiatanblud')
            ->when(request('bidang'),function($x) {
                $x->where('mappingpptkkegiatan.kodebidang', request('bidang'));
            })->when(request('kegiatan'),function($y) {
                $y->where('mappingpptkkegiatan.kodebidang', request('bidang'))
                ->where('mappingpptkkegiatan.kodekegiatan', request('kegiatan'));
            })
            ->whereBetween('tglcontrapost', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
            ->with('mapbidang', function($select){
                $select->select('mappingpptkkegiatan.kodekegiatan',
                'mappingpptkkegiatan.kegiatan',
                'mappingpptkkegiatan.kodebidang',
                'mappingpptkkegiatan.bidang');
            });

        }])
        ->where('akun50_2024.akun', '5')
        ->groupBy('akun50_2024.kodeall2')
        ->get();

        return new JsonResponse ($kode);
    }
    public function coba(){
        $awal=request('tgl');
        $akhir=request('tglx');
        $thn= date('Y');

        $kode = Akun50_2024::select('akun50_2024.kodeall2',
        'akun50_2024.uraian', 'akun50_2024.kodeall3')
        ->join('t_tampung', 't_tampung.koderek50', '=', 'akun50_2024.kodeall2')
        ->where('tgl', $thn)
        ->when(request('bidang'),function($x) {
            $x->where('t_tampung.bidang', request('bidang'));
        })->when(request('kegiatan'),function($y) {
            $y->where('t_tampung.bidang', request('bidang'))
            ->where('t_tampung.kodekegiatanblud', request('kegiatan'));
        })
        ->with('anggaran',function($tgl) use ($thn) {
            $tgl->where('tgl', $thn)
            ->select('t_tampung.koderek50',
                    't_tampung.pagu',
                    't_tampung.kodekegiatanblud',
                    't_tampung.bidang',
                    't_tampung.tgl')
            ->when(request('bidang'),function($x) {
                $x->where('t_tampung.bidang', request('bidang'));
            })->when(request('kegiatan'),function($y) {
                $y->where('t_tampung.bidang', request('bidang'))
                ->where('t_tampung.kodekegiatanblud', request('kegiatan'));
            });
        })
        ->join('npdls_rinci', 'npdls_rinci.koderek50', '=', 't_tampung.koderek50')
        ->join('npdls_heder', 'npdls_heder.nonpdls', '=', 'npdls_rinci.nonpdls')
        ->join('npkls_rinci', 'npkls_rinci.nonpdls', '=', 'npdls_heder.nonpdls')
        ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')

        ->with(['npdls_rinci' => function ($head) use ($awal, $akhir){
            $head->select('npdls_rinci.nonpdls',
                        'npdls_rinci.koderek50',
                        'npdls_rinci.rincianbelanja',
                        'npdls_rinci.nominalpembayaran')
                        // ->groupBy('npdls_rinci.koderek50')
            ->join('npdls_heder', 'npdls_heder.nonpdls', '=', 'npdls_rinci.nonpdls')
            ->join('npkls_rinci', 'npkls_rinci.nonpdls', '=', 'npdls_heder.nonpdls')
            ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
            ->where('npdls_heder.nopencairan', '!=', '')
                ->when(request('bidang'),function($x) {
                    $x->where('npdls_heder.kodebidang', request('bidang'));
                })->when(request('kegiatan'),function($y) {
                    $y->where('npdls_heder.kodebidang', request('bidang'))
                    ->where('npdls_heder.kodekegiatanblud', request('kegiatan'));
                })
                ->whereBetween('npkls_heder.tglpencairan', [$awal, $akhir])

            ->with('headerls',function($npk) use ($awal, $akhir) {
                $npk->select('npdls_heder.nonpdls',
                            'npdls_heder.tglnpdls',
                            'npdls_heder.nopencairan',
                            'npdls_heder.nonpk',
                            'npdls_heder.kodekegiatanblud',
                            'npdls_heder.kodebidang')
                ->join('npkls_rinci', 'npkls_rinci.nonpdls', '=', 'npdls_heder.nonpdls')
                ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
                ->whereBetween('npkls_heder.tglpencairan', [$awal, $akhir])
                ->with('npkrinci',function($header) use ($awal, $akhir) {
                    $header->select('npkls_rinci.nonpk','npkls_rinci.nonpdls')
                    ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
                    ->whereBetween('npkls_heder.tglpencairan', [$awal, $akhir])
                    ->with('header',function($kd){
                        $kd->select('npkls_heder.tglpencairan','npkls_heder.nonpk');
                    });
                });
            });
        },'spjpanjar'=>function($head) use ($awal,$akhir){
            $head->select('spjpanjar_rinci.nospjpanjar',
                        'spjpanjar_rinci.koderek50',
                        'spjpanjar_rinci.rincianbelanja50',
                        'spjpanjar_rinci.sisapanjar',
                        'spjpanjar_rinci.jumlahbelanjapanjar')
            ->join('spjpanjar_heder','spjpanjar_heder.nospjpanjar', '=', 'spjpanjar_rinci.nospjpanjar')
            ->whereBetween('spjpanjar_heder.tglspjpanjar', [$awal, $akhir])
            ->with('spjheader', function($nota){
                $nota->select('spjpanjar_heder.nospjpanjar',
                            'spjpanjar_heder.notapanjar',
                            'spjpanjar_heder.tglspjpanjar',
                            'spjpanjar_heder.kegiatanblud',
                            'spjpanjar_heder.kodekegiatanblud',
                            'spjpanjar_heder.kegiatan')
                ->join('notapanjar_heder', 'notapanjar_heder.nonotapanjar', '=', 'spjpanjar_heder.notapanjar')
                ->when(request('bidang'),function($x) {
                    $x->where('notapanjar_heder.kodebidang', request('bidang'));
                })->when(request('kegiatan'),function($y) {
                    $y->where('notapanjar_heder.kodebidang', request('bidang'))
                    ->where('notapanjar_heder.kodekegiatanblud', request('kegiatan'));
                })->with('nota', function($kd){
                    $kd->select('notapanjar_heder.kodebidang',
                            'notapanjar_heder.kodekegiatanblud',
                            'notapanjar_heder.nonotapanjar');
                });
            });
        },'cp' => function($tgl) use ($awal, $akhir){
            $tgl->select('contrapost.nocontrapost',
                        'contrapost.tglcontrapost',
                        'contrapost.kodekegiatanblud',
                        'contrapost.kegiatanblud',
                        'contrapost.koderek50',
                        'contrapost.rincianbelanja',
                        'contrapost.nominalcontrapost')
            ->join('mappingpptkkegiatan', 'mappingpptkkegiatan.kodekegiatan', '=', 'contrapost.kodekegiatanblud')
            ->when(request('bidang'),function($x) {
                $x->where('mappingpptkkegiatan.kodebidang', request('bidang'));
            })->when(request('kegiatan'),function($y) {
                $y->where('mappingpptkkegiatan.kodebidang', request('bidang'))
                ->where('mappingpptkkegiatan.kodekegiatan', request('kegiatan'));
            })
            ->whereBetween('tglcontrapost', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
            ->with('mapbidang', function($select){
                $select->select('mappingpptkkegiatan.kodekegiatan',
                'mappingpptkkegiatan.kegiatan',
                'mappingpptkkegiatan.kodebidang',
                'mappingpptkkegiatan.bidang');
            });

        }])
        ->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 5) as kode5'))
        // ->leftJoin('akun50_2024 as wew', DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2)'),'=','wew.kodeall3')
        ->with('kode1',function($gg){
            $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
            })
            ->with('kode2',function($gg){
                $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                })
                ->with('kode3',function($gg){
                    $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                    })
                    ->with('kode4',function($gg){
                        $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                        })
                        ->with('kode5',function($gg){
                            $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                            })
        ->where('akun50_2024.akun', '5')
        ->groupBy('akun50_2024.kodeall2')
        ->get();

        return new JsonResponse ($kode);

    }
}
