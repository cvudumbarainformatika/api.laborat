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
        $awal=request('tgl');
        $akhir=request('tglx');
        $thn= date('Y');


        $kode = Akun50_2024::where('akun50_2024.akun','5')->select('akun50_2024.kodeall2',
        DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'))
        // ->leftJoin('akun50_2024 as wawa', 'SUBSTRING_INDEX(wawa.kodeall2, ".", 1)', '=', 'kode.akun')
        // ->leftJoin('akun50_2024 as wawa', 'kode1', '=', 'ko.akun')
        ->join('npdls_rinci', 'npdls_rinci.koderek50', '=', 'akun50_2024.kodeall2')
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

        ->with(['npdls_rinci' => function ($head) use ($awal, $akhir){
            $head->select('npdls_rinci.nonpdls',
                        'npdls_rinci.koderek50',
                        'npdls_rinci.rincianbelanja',
                        'npdls_rinci.nominalpembayaran')
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
        },
        'spjpanjar'=>function($head) use ($awal,$akhir){
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

        },'anggaran' => function($tgl) use ($thn) {
            $tgl->select('t_tampung.*')->where('tgl', $thn)
            ->when(request('bidang'),function($x) {
                $x->where('t_tampung.bidang', request('bidang'));
            })->when(request('kegiatan'),function($y) {
                $y->where('t_tampung.bidang', request('bidang'))
                ->where('t_tampung.kodekegiatanblud', request('kegiatan'));
            });

        }])

        ->groupBy('akun50_2024.kodeall2')
        ->get();

       $akun = Akun50_2024::where('akun', '5')
       ->groupBy('akun50_2024.akun')
       ->get();

       $kelompok = Akun50_2024::where('akun', '5')
       ->groupBy('akun50_2024.kelompok')
       ->get();


       $all = [
        'data' => $kode,
        'akun' => $akun,
        'kelompok' => $kelompok,
       ];


        return new JsonResponse ($all);
    }
    public function coba(){
        $awal=request('tgl');
        $akhir=request('tglx');
        // PAKEJOIN
        // $kode = Akun50_2024::select('akun50_2024.*')
        // ->join('npdls_rinci', 'npdls_rinci.koderek50', '=', 'akun50_2024.kodeall2')
        // ->join('npdls_heder', 'npdls_heder.nonpdls', '=', 'npdls_rinci.nonpdls')
        // ->join('npkls_rinci', 'npkls_rinci.nonpdls', '=', 'npdls_heder.nonpdls')
        // ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
        // ->whereBetween('npkls_heder.tglpencairan', [$awal, $akhir])
        //     ->where('npdls_heder.nopencairan', '!=', '')
        //         ->when(request('bidang'),function($x) {
        //             $x->where('npdls_heder.kodebidang', request('bidang'));
        //         })->when(request('kegiatan'),function($y) {
        //             $y->where('npdls_heder.kodebidang', request('bidang'))
        //             ->where('npdls_heder.kodekegiatanblud', request('kegiatan'));
        //         })
        // ->with(['npdls_rinci' => function ($head) use ($awal, $akhir){
        //     $head->join('npdls_heder', 'npdls_heder.nonpdls', '=', 'npdls_rinci.nonpdls')
        //         ->join('npkls_rinci', 'npkls_rinci.nonpdls', '=', 'npdls_heder.nonpdls')
        //         ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
        //         ->whereBetween('npkls_heder.tglpencairan', [$awal, $akhir])
        //         ->where('npdls_heder.nopencairan', '!=', '')
        //         ->when(request('bidang'),function($x) {
        //             $x->where('npdls_heder.kodebidang', request('bidang'));
        //         })
        //         ->when(request('kegiatan'),function($y) {
        //             $y->where('npdls_heder.kodebidang', request('bidang'))
        //             ->where('npdls_heder.kodekegiatanblud', request('kegiatan'));
        //         })

        //     ->with('headerls',function($npk) use ($awal, $akhir) {
        //         $npk->join('npkls_rinci', 'npkls_rinci.nonpdls', '=', 'npdls_heder.nonpdls')
        //             ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
        //             ->whereBetween('npkls_heder.tglpencairan', [$awal, $akhir])
        //         ->with('npkrinci',function($header) use ($awal, $akhir) {
        //             $header->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
        //             ->whereBetween('npkls_heder.tglpencairan', [$awal, $akhir])
        //             ->with('header');
        //         });
        //     });
        // },
        // 'spjpanjar'=>function($head) use ($awal,$akhir){
        //     $head->join('spjpanjar_heder','spjpanjar_heder.nospjpanjar', '=', 'spjpanjar_rinci.nospjpanjar')
        //         ->whereBetween('spjpanjar_heder.tglspjpanjar', [$awal, $akhir])
        //         ->with('spjheader');
        // },'cp' => function($tgl) use ($awal, $akhir){
        //     $tgl->whereBetween('tglcontrapost', [$awal. ' 00:00:00', $akhir. ' 23:59:59']);
        // }])
        // ->get();

        // PAKE WHEREHAS
        $kode = Akun50_2024::whereHas('npdls_rinci.headerls.npkrinci.header',function($zzz) use ($awal, $akhir){
            $zzz->where('nopencairan', '!=', '')
            ->when(request('bidang'),function($x) {
                $x->where('kodebidang', request('bidang'));
            })->when(request('kegiatan'),function($y) {
                $y->where('kodebidang', request('bidang'))
                ->where('kodekegiatanblud', request('kegiatan'));
            })
            ->whereBetween('tglpencairan', [$awal, $akhir]);
        })
        ->whereHas('npdls_rinci.headerls',function ($cair) {
            $cair->where('nopencairan', '!=', '')
                ->when(request('bidang'),function($x) {
                    $x->where('kodebidang', request('bidang'));
                })->when(request('kegiatan'),function($y) {
                    $y->where('kodebidang', request('bidang'))
                    ->where('kodekegiatanblud', request('kegiatan'));
                });
        })
        ->with(['npdls_rinci' => function ($head) use ($awal, $akhir){
            $head->whereHas('headerls.npkrinci.header',function($zzz) use ($awal, $akhir){
                $zzz->whereBetween('tglpencairan', [$awal, $akhir]);
            })
            ->whereHas('headerls',function ($cair) {
                $cair->where('nopencairan', '!=', '')
                ->when(request('bidang'),function($x) {
                    $x->where('kodebidang', request('bidang'));
                })
                ->when(request('kegiatan'),function($y) {
                    $y->where('kodebidang', request('bidang'))
                    ->where('kodekegiatanblud', request('kegiatan'));
                });
            })
            ->with('headerls',function($npk) use ($awal, $akhir) {
                $npk->whereHas('npkrinci.header',function($zzz) use ($awal, $akhir){
                    $zzz->whereBetween('tglpencairan', [$awal, $akhir]);
                })
                ->with('npkrinci',function($header) use ($awal, $akhir) {
                    $header->whereHas('header',function($zzz) use ($awal, $akhir){
                        $zzz->whereBetween('tglpencairan', [$awal, $akhir]);
                    })->with('header');
                });
            });
        },
        'spjpanjar'=>function($head) use ($awal,$akhir){
            $head->whereHas('spjheader',function($cair) use ($awal,$akhir){
                $cair->whereBetween('tglspjpanjar', [$awal, $akhir]);
            })->with('spjheader');
        },'cp' => function($tgl) use ($awal, $akhir){
            $tgl->whereBetween('tglcontrapost', [$awal. ' 00:00:00', $akhir. ' 23:59:59']);
        }])
        ->get();


        $latest=Akun_Kepmendg50::with('npdls_rinci')
        ->where('kode1', '5')
        ->get();

        return new JsonResponse ($latest);

    }
}
