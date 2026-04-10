<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Penyesuaian_Prioritas_Header;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RBAController extends Controller
{

    public function getDatarba() {
        $thn = request('tahun', 'Y');
        $anggaran = PergeseranPaguRinci::where('tgl', $thn)
        ->where('t_tampung.pagu', '!=', 0)
        ->select(
            't_tampung.usulan',
            't_tampung.pagu',
            't_tampung.koderek108',
            't_tampung.koderek50',
            't_tampung.kodekegiatanblud as kodekegiatan',
            't_tampung.bidang',
            't_tampung.volume',
            't_tampung.harga',
            't_tampung.satuan',
            't_tampung.idpp',
            'akun50_2024.kodeall3 as kode',
            'akun50_2024.uraian as uraian',
            'mappingpptkkegiatan.kodekegiatan',
            'mappingpptkkegiatan.kegiatan',
            'mappingpptkkegiatan.kodebidang',
            'mappingpptkkegiatan.bidang',
            DB::raw('0 as paguawal'),
            DB::raw('0 as hargaawal'),
            DB::raw('0 as volumeawal'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(t_tampung.koderek50, ".", 1) LIMIT 1) as uraian1'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(t_tampung.koderek50, ".", 2) LIMIT 1) as uraian2'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(t_tampung.koderek50, ".", 3) LIMIT 1) as uraian3'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(t_tampung.koderek50, ".", 4) LIMIT 1) as uraian4'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(t_tampung.koderek50, ".", 5) LIMIT 1) as uraian5')
        )->join('akun50_2024', 'akun50_2024.kodeall2', '=', 't_tampung.koderek50')
        ->join('mappingpptkkegiatan', 'mappingpptkkegiatan.kodekegiatan', '=', 't_tampung.kodekegiatanblud')
        ->orderBy('kode', 'asc')
        ->get();

        $anggaranawal = Penyesuaian_Prioritas_Header::whereBetween('penyesesuaianperioritas_heder.tgltrans', [$thn.'-01-01', $thn.'-12-31'])
            ->leftJoin('penyesesuaianperioritas_rinci', 'penyesesuaianperioritas_rinci.notrans', '=', 'penyesesuaianperioritas_heder.notrans')
            ->leftJoin('akun50_2024', 'akun50_2024.kodeall2', '=', 'penyesesuaianperioritas_rinci.koderek50')
            ->select(
                'penyesesuaianperioritas_heder.notrans',
                'penyesesuaianperioritas_heder.namabidang as bidang',
                'penyesesuaianperioritas_heder.kodebidang',
                'penyesesuaianperioritas_heder.kodekegiatan',
                'penyesesuaianperioritas_rinci.id as idpp',
                'penyesesuaianperioritas_rinci.usulan',
                'penyesesuaianperioritas_rinci.koderek108',
                'penyesesuaianperioritas_rinci.koderek50',
                'penyesesuaianperioritas_rinci.uraian108',
                'penyesesuaianperioritas_rinci.jumlahacc as volumeawal',
                'penyesesuaianperioritas_rinci.harga as hargaawal',
                'penyesesuaianperioritas_rinci.nilai as paguawal',
                'penyesesuaianperioritas_rinci.satuan as satuanawal',
                'akun50_2024.kodeall3 as kode',
                'akun50_2024.uraian as uraian',
                DB::raw('0 as pagu'),
                DB::raw('0 as harga'),
                DB::raw('0 as volume'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(penyesesuaianperioritas_rinci.koderek50, ".", 1) LIMIT 1) as uraian1'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(penyesesuaianperioritas_rinci.koderek50, ".", 2) LIMIT 1) as uraian2'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(penyesesuaianperioritas_rinci.koderek50, ".", 3) LIMIT 1) as uraian3'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(penyesesuaianperioritas_rinci.koderek50, ".", 4) LIMIT 1) as uraian4'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(penyesesuaianperioritas_rinci.koderek50, ".", 5) LIMIT 1) as uraian5'),
            )
            
            ->get();
        return new JsonResponse([
            'anggaran' => $anggaran,
            'anggaranawal' => $anggaranawal
        ]);
    }
}
