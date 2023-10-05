<?php

namespace App\Http\Controllers\Api\Simrs\Planing;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Msistembayar;
use App\Models\Simrs\Penunjang\Kamaroperasi\JadwaloperasiController;
use App\Models\Simrs\Planing\Mplaning;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Rajal\Listkonsulantarpoli;
use App\Models\Simrs\Rajal\WaktupulangPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaningController extends Controller
{
    public function mpoli()
    {
        $mpoli = Mpoli::where('rs5', 1)
            ->where('rs4', 'Poliklinik')
            ->get();
        return new JsonResponse($mpoli);
    }
    public function mpalningrajal()
    {
        $mplanrajal = Mplaning::where('hidden', '!=', '1')->where('unit', 'RJ')->get();
        return new JsonResponse($mplanrajal);
    }

    public function simpanplaningpasien(Request $request)
    {
        $sistembayar = Msistembayar::select('groups')->where('rs1', $request->kodesistembayar)->first();

        if ($request->planing == 'Konsultasi') {
            $simpanplaningpasien = self::simpankonsulantarpoli($request);
            if ($simpanplaningpasien == 500) {
                return new JsonResponse(['message' => 'Maaf, Data Pasien Ini Masih Ada Dalam List Konsulan TPPRJ...!!!'], 500);
            }
            $simpanakhir = self::simpanakhir($request);
            if ($simpanakhir == 500) {
                return new JsonResponse(['message' => 'Maaf, Data Pasien Ini Masih Ada Dalam List Konsulan TPPRJ...!!!'], 500);
            }
            return new JsonResponse(['message' => 'Berhasil Mengirim Data Ke List Konsulan TPPRJ Pasien Ini...!!!'], 200);
        } elseif ($request->planing == 'Rumah Sakit Lain') {
            if ($sistembayar == '1') {
                $createrujukan = BridbpjsplanController::bridcretaerujukan($request);
                if ($createrujukan == 500) {
                    return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                } elseif ($createrujukan == 200) {
                    $simpanakhir = self::simpanakhir($request);
                    if ($simpanakhir == 500) {
                        return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                    }
                    return new JsonResponse(['message' => 'Data Berhasil Disimpan'], 500);
                } else {
                    return $createrujukan;
                }
            } else {
                $simpanakhir = self::simpanakhir($request);
                if ($simpanakhir == 500) {
                    return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                }
                return new JsonResponse(['message' => 'Data Berhasil Disimpan'], 500);
            }
        } elseif ($request->planing == 'Rawat Inap') {
            if ($request->status == 'Operasi' && $sistembayar == '1') {
                $createspri = BridbpjsplanController::createspri($request);
                $xxx = $createspri['metadata']['code'];
                if ($xxx === 200 || $xxx === '200') {
                }
                $simpanop = self::jadwaloperasi($request);
                if ($simpanop == 500) {
                    return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                }
                return new JsonResponse(['message' => 'Data Berhasil Disimpan...!!!'], 200);
            }
        } else {
            $simpanakhir = self::simpanakhir($request);
            if ($simpanakhir == 500) {
                return new JsonResponse(['message' => 'Maaf, Data Pasien Ini Masih Ada Dalam List Konsulan TPPRJ...!!!'], 500);
            }
            return new JsonResponse(['message' => 'Berhasil Mengirim Data Ke List Konsulan TPPRJ Pasien Ini...!!!'], 200);
        }
    }

    public static function simpankonsulantarpoli($request)
    {
        $cek = Listkonsulantarpoli::where('noreg_lama', $request->noreg_lama)->where('flag', '')->count();
        if ($cek > 0) {
            return 500;
        }
        $simpankonsulantarpoli = Listkonsulantarpoli::firstOrCreate(
            [
                'noreg_lama' => $request->noreg_lama
            ],
            [
                'norm' => $request->norm,
                'tgl_kunjungan' => $request->tgl_kunjungan,
                'tgl_rencana_konsul' => $request->tgl_rencana_konsul,
                'kdpoli_asal' => $request->kdpoli_asal,
                'kdpoli_tujuan' => $request->kdpoli_tujuan,
                'kddokter_asal' => $request->kddokter_asal
            ]
        );

        if (!$simpankonsulantarpoli) {
            return 500;
        }

        // $updatekunjungan = KunjunganPoli::where('rs1', $request->noreg_lama)->first();
        // $updatekunjungan->rs19 = '1';
        // $updatekunjungan->rs24 = '1';
        // $updatekunjungan->save();
        // ->update(
        //     [
        //         'rs19' => 1,
        //         'rs24' => 1
        //     ]
        // );
        // if (!$updatekunjungan) {
        //     return 500;
        // }
        return 200;
    }

    public static function simpanakhir($request)
    {
        $simpanakhir = WaktupulangPoli::firstOrCreate(
            [
                'rs1' => $request->noreg,
                'rs2' => $request->norm,
                'rs3' => $request->kdpoli_tujuan,
                'rs4' => $request->planing,
                'tgl' => date('Y-m-d H:i:s'),
                'user' => auth()->user()->pegawai_id
            ]
        );

        if (!$simpanakhir) {
            return 500;
        }
        return 200;
    }

    public static function jadwaloperasi($request)
    {
        $simpan = JadwaloperasiController::firstOrCreate(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'nopermintaan' => $request->nopermintaan,
                'kodebooking' => $request->norekodebookingg,
                'tanggaloperasi' => $request->tanggaloperasi,
                'jenistindakan' => $request->jenistindakan,
                'icd9' => $request->icd9,
                'kodepoli' => $request->kodepoli,
                'namapoli' => $request->namapoli,
                'lastupdate' => $request->lastupdate,
                'userid' => auth()->user()->pegawai_id,
                'kdruang' => $request->kdruang,
                'tglupdate' => $request->tglupdate,
                'kddokter' => $request->kddokter,
                'dokter' => $request->dokter,
                'kdruangtujuan' => $request->kdruangtujuan,
                'kontakpasien' => $request->kontakpasien,
                'jenisoperasi' => $request->kddokter,
                'terlaksana' => 0
            ]
        );
        if (!$simpan) {
            return 500;
        }
        return 200;
    }
}
