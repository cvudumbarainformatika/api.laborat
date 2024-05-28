<?php

namespace App\Http\Controllers\Api\Siasik\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Master\Akun50_2024;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LRAController extends Controller
{
    public function lra(){
        $awal=request('tglmulai', '2024-03-01');
        $akhir=request('tglakhir', '2024-03-31');
        $kode = Akun50_2024::whereHas('npdls_rinci.headerls.npkrinci.header',function($zzz) use ($awal, $akhir){
            $zzz->whereBetween('tglpencairan', [$awal, $akhir]);
        })
        ->whereHas('npdls_rinci.headerls',function ($cair) {
            $cair->where('nopencairan', '!=', '')
                ->when(request('kode'),function($x) {
                    $x->where('kodebidang', request('kode'));
                })->when(request('keg'),function($y) {
                    $y->where('kodebidang', request('kode'))
                    ->where('kodekegiatanblud', request('keg'));
                });
        })
        ->with(['npdls_rinci' => function ($head) use ($awal, $akhir){
            $head->whereHas('headerls.npkrinci.header',function($zzz) use ($awal, $akhir){
                $zzz->whereBetween('tglpencairan', [$awal, $akhir]);
            })
            ->whereHas('headerls',function ($cair) {
                $cair->where('nopencairan', '!=', '')
                ->when(request('kode'),function($x) {
                    $x->where('kodebidang', request('kode'));
                })
                ->when(request('keg'),function($y) {
                    $y->where('kodebidang', request('kode'))
                    ->where('kodekegiatanblud', request('keg'));
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
        return new JsonResponse ($kode);
    }
    public function coba(){
        $awal=request('tglmulai', '2024-03-01');
        $akhir=request('tglakhir', '2024-03-31');
        $kode = Akun50_2024::
        whereHas('npdls_rinci.headerls',function ($cair) {
            $cair->where('nopencairan', '!=', '')
                ->when(request('kode'),function($x) {
                    $x->where('kodebidang', request('kode'));
                })->when(request('keg'),function($y) {
                    $y->where('kodebidang', request('kode'))
                    ->where('kodekegiatanblud', request('keg'));
                });
        })
        ->with(['npdls_rinci' => function ($head) use ($awal, $akhir){
            $head->whereHas('headerls',function ($cair) {
                $cair->where('nopencairan', '!=', '')
                ->when(request('kode'),function($x) {
                    $x->where('kodebidang', request('kode'));
                })

                ->when(request('keg'),function($y) {
                    $y->where('kodebidang', request('kode'))
                    ->where('kodekegiatanblud', request('keg'));
                });
            })
            ->with('headerls',function($npk) use ($awal, $akhir) {
                $npk->with('npkrinci',function($header) use ($awal, $akhir) {
                    $header->with('header', function($tgl) use ($awal, $akhir){
                        $tgl->whereBetween('tglpencairan', [$awal, $akhir]);
                    });
                });
            });
        },
        'spjpanjar','cp'])
        ->get();
        return new JsonResponse ($kode);


    }
}
