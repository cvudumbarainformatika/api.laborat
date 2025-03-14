<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\Master\Mapping_Bidang_Ptk_Kegiatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CetakAnggaranController extends Controller
{
    public function bidangbidangkegiatan(){
        $thn= request('tahun', 'Y');
        $bidangkegiatan=Mapping_Bidang_Ptk_Kegiatan::where('tahun', $thn)
        ->where('alias', '!=', '')
        ->when(request('bidang'),function($keg) {
            $keg->where('kodebidang', request('bidang'));
        })
        ->select('kodebidang', 'bidang', 'kodekegiatan', 'kegiatan', 'kodepptk', 'namapptk')
        ->groupBy('kodekegiatan')
        ->get();

        return new JsonResponse($bidangkegiatan);

    }

    public function getAnggaran() {
        $thn= request('tahun', 'Y');
        $anggaran = PergeseranPaguRinci::where('tgl', $thn)
        ->where('t_tampung.pagu', '!=', 0)
        ->where('t_tampung.bidang', request('bidang'))
        ->where('t_tampung.kodekegiatanblud', request('kegiatan'))
        ->select(
            't_tampung.usulan',
            't_tampung.pagu',
            't_tampung.koderek108',
            't_tampung.koderek50',
            't_tampung.kodekegiatanblud',
            't_tampung.volume',
            't_tampung.harga',
            't_tampung.satuan',
            'akun50_2024.kodeall3 as kode',
            'akun50_2024.uraian as uraian'
        )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 5) as kode5'))
        ->join('akun50_2024', 'akun50_2024.kodeall2', '=', 't_tampung.koderek50')
        ->with(['lvl1' => function($sel){
            $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
        }, 'lvl2' => function($sel){
            $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
        },'lvl3' => function($sel){
            $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
        },'lvl4' => function($sel){
            $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
        },'lvl5' => function($sel){
            $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
        }])
        ->orderBy('kode', 'asc')
        ->get();

        return new JsonResponse($anggaran);
    }
}
