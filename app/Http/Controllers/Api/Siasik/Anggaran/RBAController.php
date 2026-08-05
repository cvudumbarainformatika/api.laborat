<?php

namespace App\Http\Controllers\Api\Siasik\Anggaran;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Anggaran\Penyesuaian_Prioritas_Header;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\Anggaran\Perubahan_pak_header;
use App\Models\Siasik\Anggaran\Perubahan_pak_rinci;
use App\Models\Siasik\Anggaran\Tampungcopy;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RBAController extends Controller
{

    public function getDatarba() {
        $thn = request('tahun', 'Y');
        $anggaran = PergeseranPaguRinci::where('tgl', $thn)
        ->where('t_tampung.pagu', '!=', 0)
        ->leftJoin('akun50_2024', function ($join) {
                        $join->on('t_tampung.koderek50', '=', 'akun50_2024.kodeall2')
                            ->orOn('t_tampung.koderek50', '=', 'akun50_2024.kodeall3');
                            
                    })
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
        )
        // ->join('akun50_2024', 'akun50_2024.kodeall2', '=', 't_tampung.koderek50')
        ->join('mappingpptkkegiatan', 'mappingpptkkegiatan.kodekegiatan', '=', 't_tampung.kodekegiatanblud')
        ->orderBy('kode', 'asc')
        ->get();

        $anggaranawal = Penyesuaian_Prioritas_Header::whereBetween('penyesesuaianperioritas_heder.tgltrans', [$thn.'-01-01', $thn.'-12-31'])
            ->leftJoin('penyesesuaianperioritas_rinci', 'penyesesuaianperioritas_rinci.notrans', '=', 'penyesesuaianperioritas_heder.notrans')
            // ->leftJoin('akun50_2024', 'akun50_2024.kodeall2', '=', 'penyesesuaianperioritas_rinci.koderek50')
            ->leftJoin('akun50_2024', function ($join) {
                        $join->on('penyesesuaianperioritas_rinci.koderek50', '=', 'akun50_2024.kodeall2')
                            ->orOn('penyesesuaianperioritas_rinci.koderek50', '=', 'akun50_2024.kodeall3');
                            
                    })
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



        // UNTUK PAK
        $usulan_awal = Tampungcopy::where('tgl', $thn)
        ->where('t_tampung_copy.pagu', '!=', 0)
        ->leftJoin('akun50_2024', function ($join) {
                        $join->on('t_tampung_copy.koderek50', '=', 'akun50_2024.kodeall2')
                            ->orOn('t_tampung_copy.koderek50', '=', 'akun50_2024.kodeall3');
                            
                    })
        ->select(
            't_tampung_copy.usulan',
            't_tampung_copy.pagu as paguawal',
            't_tampung_copy.koderek108',
            't_tampung_copy.koderek50',
            't_tampung_copy.kodekegiatanblud as kodekegiatan',
            't_tampung_copy.bidang',
            't_tampung_copy.volume as volumeawal',
            't_tampung_copy.harga as hargaawal',
            't_tampung_copy.satuan',
            't_tampung_copy.idpp',
            'akun50_2024.kodeall3 as kode',
            'akun50_2024.uraian as uraian',
            'mappingpptkkegiatan.kodekegiatan',
            'mappingpptkkegiatan.kegiatan',
            'mappingpptkkegiatan.kodebidang',
            'mappingpptkkegiatan.bidang',
            DB::raw('0 as pagu'),
            DB::raw('0 as harga'),
            DB::raw('0 as volume'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(t_tampung_copy.koderek50, ".", 1) LIMIT 1) as uraian1'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(t_tampung_copy.koderek50, ".", 2) LIMIT 1) as uraian2'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(t_tampung_copy.koderek50, ".", 3) LIMIT 1) as uraian3'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(t_tampung_copy.koderek50, ".", 4) LIMIT 1) as uraian4'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(t_tampung_copy.koderek50, ".", 5) LIMIT 1) as uraian5')
        )
        // ->join('akun50_2024', 'akun50_2024.kodeall2', '=', 't_tampung_copy.koderek50')
        ->join('mappingpptkkegiatan', 'mappingpptkkegiatan.kodekegiatan', '=', 't_tampung_copy.kodekegiatanblud')
        ->orderBy('kode', 'asc')
        ->get();

        $usulan_pak = Perubahan_pak_header::whereBetween('usulanHonor_h_pak.tglTransaksi', [
                $thn . '-01-01',
                $thn . '-12-31',
            ])
            ->leftJoin('usulanHonor_r_pak', 'usulanHonor_h_pak.notrans', '=', 'usulanHonor_r_pak.notrans')
            ->leftJoin('akun50_2024', function ($join) {
                    $join->on('usulanHonor_r_pak.koderek50', '=', 'akun50_2024.kodeall2')
                        ->orOn('usulanHonor_r_pak.koderek50', '=', 'akun50_2024.kodeall3');
                        
                })
            ->select(
                'usulanHonor_r_pak.keterangan as usulan',
                'usulanHonor_r_pak.nilai as pagu',
                'usulanHonor_r_pak.koderek50',
                'usulanHonor_r_pak.koderek108',
                'usulanHonor_r_pak.volume as volume',
                'usulanHonor_r_pak.harga as harga',
                'usulanHonor_r_pak.satuan as satuan',
                'usulanHonor_r_pak.idpp as idpp',
                'usulanHonor_h_pak.kodeKegiatan as kodekegiatan',
                'usulanHonor_h_pak.ruangan as bidang',
                'usulanHonor_h_pak.kodebagian as kodebidang',
                
                'akun50_2024.kodeall3 as kode',
                'akun50_2024.uraian as uraian',
                DB::raw('0 as paguawal'),
                DB::raw('0 as hargaawal'),
                DB::raw('0 as volumeawal'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(usulanHonor_r_pak.koderek50, ".", 1) LIMIT 1) as uraian1'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(usulanHonor_r_pak.koderek50, ".", 2) LIMIT 1) as uraian2'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(usulanHonor_r_pak.koderek50, ".", 3) LIMIT 1) as uraian3'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(usulanHonor_r_pak.koderek50, ".", 4) LIMIT 1) as uraian4'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall2 = SUBSTRING_INDEX(usulanHonor_r_pak.koderek50, ".", 5) LIMIT 1) as uraian5')
            )
            // ->where('r.koderek50', $request->koderek50)
            ->orderBy('kode', 'asc')
            ->get();

        return new JsonResponse([
            'anggaran' => $anggaran,
            'anggaranawal' => $anggaranawal,
            'usulan_awal' => $usulan_awal,
            'usulan_pak' => $usulan_pak
        ]);
    }
}
