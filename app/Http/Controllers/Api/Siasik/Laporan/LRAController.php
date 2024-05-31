<?php

namespace App\Http\Controllers\Api\Siasik\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Siasik\Master\Mapping_Bidang_Ptk_Kegiatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function kegiatan(){
        // $thn=request('tahun');
        $thn= date('Y');
        $kegiatan=Mapping_Bidang_Ptk_Kegiatan::where('tahun', $thn)
        ->select('kodekegiatan', 'kegiatan')
        ->groupBy('kodekegiatan')
        ->get();
        return new JsonResponse($kegiatan);

    }

    public function laplra(){
        $awal=request('tgl');
        $akhir=request('tglx');
        $kode = Akun50_2024::whereHas('npdls_rinci.headerls.npkrinci.header',function($zzz) use ($awal, $akhir){
            $zzz->whereBetween('tglpencairan', [$awal, $akhir]);
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
        // ->where('kodeall3', '5')
        // ->orwhere('kodeall3', '5.1')
        // ->orWhere('kodeall3', '5.2')
        // ->orWhere('kodeall3', '03')
        // ->orWhere('kodeall3', '04')
        // ->orWhere('kodeall3', '05')
        // ->orWhere('kodeall3', '06')
        ->get();

        return new JsonResponse ($kode);
    }
    public function coba(){
        $awal=request('tgl');
        $akhir=request('tglx');
        $kode = Akun50_2024::
        // ->where('kelompok', '')->orWhere('jenis', '')
        whereHas('npdls_rinci.headerls',function ($cair) {
            $cair->where('nopencairan', '!=', '')
                ->when(request('bidang'),function($x) {
                    $x->where('kodebidang', request('bidang'));
                })->when(request('kegiatan'),function($y) {
                    $y->where('kodebidang', request('bidang'))
                    ->where('kodekegiatanblud', request('kegiatan'));
                });
        })->
        with(['npdls_rinci' => function ($head) use ($awal, $akhir){
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

        // ->orWhere('kodeall3', '03')
        // ->orWhere('kodeall3', '04')
        // ->orWhere('kodeall3', '05')
        // ->orWhere('kodeall3', '06')
        ->get();

        return new JsonResponse ($kode);


    }
}
