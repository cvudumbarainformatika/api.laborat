<?php

namespace App\Http\Controllers\Api\Simrs\Planing;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Msistembayar;
use App\Models\Simrs\Penunjang\Kamaroperasi\JadwaloperasiController;
use App\Models\Simrs\Planing\Mplaning;
use App\Models\Simrs\Planing\Simpanspri;
use App\Models\Simrs\Planing\Simpansuratkontrol;
use App\Models\Simrs\Planing\Transrujukan;
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
        $cek = WaktupulangPoli::where('rs1', $request->noreg)->count();
        if ($cek > 0) {
            return new JsonResponse(['message' => 'Maaf, data kunjungan pasien ini sudah di rencanakan...!!!'], 500);
        }
        $sistembayar = Msistembayar::select('groups')->where('rs1', $request->kodesistembayar)->first();
        $groupsistembayar = $sistembayar->groups;
        // $groupsistembayar = '1';
        if ($request->planing == 'Konsultasi') {
            $simpanplaningpasien = self::simpankonsulantarpoli($request);
            if ($simpanplaningpasien == 500) {
                return new JsonResponse(['message' => 'Maaf, Data Pasien Ini Masih Ada Dalam List Konsulan TPPRJ...!!!'], 500);
            }
            $simpanakhir = self::simpanakhir($request);
            if ($simpanakhir == 500) {
                return new JsonResponse(['message' => 'Maaf, Data Pasien Ini Masih Ada Dalam List Konsulan TPPRJ...!!!'], 500);
            }
            $data = WaktupulangPoli::where('rs1', $request->noreg)->first();
            return new JsonResponse([
                'message' => 'Berhasil Mengirim Data Ke List Konsulan TPPRJ Pasien Ini...!!!',
                'result' => $data->load('masterpoli')
            ], 200);
        } elseif ($request->planing == 'Rumah Sakit Lain') {
            if ($groupsistembayar == '1') {
                $createrujukan = BridbpjsplanController::bridcretaerujukan($request);
                if ($createrujukan == 500) {
                    return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                } elseif ($createrujukan == 200) {
                    $simpanakhir = self::simpanakhir($request);
                    if ($simpanakhir == 500) {
                        return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                    }
                    $data = WaktupulangPoli::where('rs1', $request->noreg)->first();
                    // $data = Transrujukan::with(
                    //     ['masterpasien', 'relmpoli', 'relmpolix', 'rs141']
                    // )
                    //     ->where('rs1', $request->noreg)->first();
                    return new JsonResponse([
                        'message' => 'Data Berhasil Disimpan',
                        'result' => $data->load('masterpoli')
                    ], 200);
                } else {
                    return $createrujukan;
                }
            } else {
                $simpanakhir = self::simpanakhir($request);
                if ($simpanakhir == 500) {
                    return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!',], 500);
                }
                $simpanrujukanumum = self::simpanrujukanumum($request);
                if ($simpanrujukanumum == 500) {
                    return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan...!!!',], 500);
                }
                $data = WaktupulangPoli::where('rs1', $request->noreg)->first();
                // $data = Transrujukan::with(
                //     ['masterpasien', 'relmpoli', 'relmpolix', 'rs141']
                // )
                //     ->where('rs1', $request->noreg)->first();
                return new JsonResponse([
                    'message' => 'Data Berhasil Disimpan',
                    'result' => $data->load('masterpoli')
                ], 200);
            }
        } elseif ($request->planing == 'Rawat Inap') {
            if ($request->status == 'Operasi') {
                if ($groupsistembayar == '1') {
                    $createspri = BridbpjsplanController::createspri($request);
                    $nospri = $createspri['response']['noSPRI'];
                    $xxx = $createspri['metadata']['code'];
                    if ($xxx === 200 || $xxx === '200') {
                        $simpanop = self::jadwaloperasi($request);
                        if ($simpanop == 500) {
                            return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                        }
                        $simpanspri = self::simpanspri($request, $groupsistembayar, $nospri);
                        $simpanakhir = self::simpanakhir($request);
                        if ($simpanspri === 500) {
                            return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                        }
                        $data = WaktupulangPoli::where('rs1', $request->noreg)->first();
                        return new JsonResponse([
                            'message' => 'Data Berhasil Disimpan...!!!',
                            'result' => $data->load('masterpoli')
                        ], 200);
                    }
                } else {
                    $nospri = $request->noreg;
                    $simpanop = self::jadwaloperasi($request);
                    // return $simpanop;
                    if ($simpanop == 500) {
                        return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                    }
                    $simpanspri = self::simpanspri($request, $groupsistembayar, $nospri);
                    if ($simpanspri === 500) {
                        return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                    }
                    $simpanakhir = self::simpanakhir($request);
                    $data = WaktupulangPoli::where('rs1', $request->noreg)->first();
                    return new JsonResponse([
                        'message' => 'Data Berhasil Disimpan...!!!',
                        'result' => $data->load('masterpoli')
                    ], 200);
                }
            } else {
                if ($groupsistembayar == '1') {
                    $createspri = BridbpjsplanController::createspri($request);
                    $nospri = $createspri['response']['noSPRI'];
                    $xxx = $createspri['metadata']['code'];
                    if ($xxx === 200 || $xxx === '200') {
                        $simpanspri = self::simpanspri($request, $groupsistembayar, $nospri);
                        if ($simpanspri === 500) {
                            return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                        }
                        $simpanakhir = self::simpanakhir($request);
                        $data = Simpanspri::where('noreg', $request->noreg)->first();
                        return new JsonResponse([
                            'message' => 'Data Berhasil Disimpan...!!!',
                            'result' => $data
                        ], 200);
                    }
                } else {
                    $nospri = $request->noreg;
                    $simpanspri = self::simpanspri($request, $groupsistembayar, $nospri);
                    $simpanakhir = self::simpanakhir($request);
                    $data = Simpanspri::where('noreg', $request->noreg)->first();
                    if ($simpanspri === 500) {
                        return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                    }
                    return new JsonResponse([
                        'message' => 'Data Berhasil Disimpan...!!!',
                        'result' => $data->load('masterpoli')
                    ], 200);
                }
            }
        } else {
            if ($groupsistembayar == '1') {
                $simpan = BridbpjsplanController::insertsuratcontrol($request);
                $nosuratkontrol = $simpan['response']['noSuratKontrol'];
                $xxx = $simpan['metadata']['code'];
                if ($xxx === 200 || $xxx === '200') {
                    $simpanspri = self::simpansuratkontrol($request, $groupsistembayar, $nosuratkontrol);
                    $simpanakhir = self::simpanakhir($request);
                    $data = Simpansuratkontrol::where('noreg', $request->noreg)->firs();
                    return new JsonResponse([
                        'message' => 'Data Berhasil Disimpan...!!!',
                        'result' => $data
                    ], 200);
                } else {
                    return new JsonResponse($simpan);
                }
            } else {
                $nosuratkontrol = $request->noreg;
                $simpanspri = self::simpansuratkontrol($request, $groupsistembayar, $nosuratkontrol);
                $simpanakhir = self::simpanakhir($request);
                $data = Simpansuratkontrol::where('noreg', $request->noreg)->firs();
                return new JsonResponse([
                    'message' => 'Data Berhasil Disimpan...!!!',
                    'result' => $data
                ], 200);
            }
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
                'kddokter_asal' => $request->kddokter_asal ?? ''
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
        if ($request->planing == 'Konsultasi') {
            $simpanakhir = WaktupulangPoli::create(
                [
                    'rs1' => $request->noreg ?? '',
                    'rs2' => $request->norm ?? '',
                    'rs3' => $request->kdpoli_tujuan ?? '',
                    'rs4' => $request->planing ?? '',
                    // 'rs5' => $request->kdpoli_asal ?? '',
                    'tgl' => date('Y-m-d H:i:s'),
                    'user' => auth()->user()->pegawai_id
                ]
            );
        } else {
            $simpanakhir = WaktupulangPoli::create(
                [
                    'rs1' => $request->noreg ?? '',
                    'rs2' => $request->norm ?? '',
                    'rs3' => $request->kdruang ?? '',
                    'rs4' => $request->planing ?? '',
                    'rs5' => $request->kdruangtujuan ?? '',
                    'tgl' => date('Y-m-d H:i:s'),
                    'user' => auth()->user()->pegawai_id
                ]
            );
        }

        if (!$simpanakhir) {
            return 500;
        }
        return 200;
    }

    public static function jadwaloperasi($request)
    {
        $conter = JadwaloperasiController::count();
        $kodebooking = "JO/" . ($conter + 1) . "/" . date("d/m/Y");
        $simpan = JadwaloperasiController::firstOrCreate(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                //    'nopermintaan' => $request->nopermintaan,
                'kodebooking' => $kodebooking,
                'tanggaloperasi' => $request->tanggaloperasi,
                'jenistindakan' => $request->jenistindakan,
                'icd9' => $request->icd9,
                'kodepoli' => $request->kodepolibpjs,
                'namapoli' => $request->polibpjs,
                'lastupdate' => time(),
                'ket' => $request->keterangan,
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

    public function hapusplaningpasien(Request $request)
    {
        $cari = WaktupulangPoli::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }

        Listkonsulantarpoli::where('noreg_lama', $cari->rs1)->delete();
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }

        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }

    public static function simpanrujukanumum($request)
    {
        $simpanrujukan = Transrujukan::create(
            [
                'rs1' => $request->noreg,
                'rs2' => $request->norm,
                //    'rs3' => $norujukan,
                // 'rs4' => $request->nosep,
                'rs5' => $request->tglrujukan,
                'rs6' => $request->ppkdirujuk,
                'rs7' => $request->ppkdirujukx,
                'rs8' => $request->jenispelayanan,
                'rs9' => $request->catatan,
                'rs10' => $request->diagnosarujukan,
                'rs11' => $request->tiperujukan,
                'rs12' => $request->kodepoli,
                'rs13' => date('Y-m-d H:i:s'),
                'rs14' => auth()->user()->pegawai_id,
                //   'rs15' => $request->noka,
                'rs16' => $request->nama,
                'rs17' => $request->kelamin,
                'tglRencanaKunjungan' => $request->tglrencanakunjungan,
                'diagnosa' => $request->diagnosa,
                'poli' => $request->kodepoli,
                //    'tipefaskes' => $request->tipefaskes,
                'polix' => $request->polirujukan
            ]
        );

        if (!$simpanrujukan) {
            return 500;
        }
        return 200;
    }

    public static function simpanspri($request, $groupsistembayar, $nospri)
    {
        $simpanspri = Simpanspri::firstOrCreate(
            [
                'noSuratKontrol' => $nospri
            ],
            [
                'noreg' => $request->noreg,
                'norm' => $request->noreg,
                'kodeDokter' => $request->kddokter,
                'poliKontrol' => $request->kodepolibpjs,
                'tglRencanaKontrol' => $request->tglrencanakunjungan,
                'namaDokter' => $request->dokter,
                'noKartu' => $request->noka,
                'nama' => $request->nama,
                'kelamin' => $request->kelamin,
                'tglLahir' => $request->tgllahir,
                'user_id' => auth()->user()->pegawai_id
            ]
        );
        if (!$simpanspri) {
            return 500;
        }
        return 200;
    }

    public static function simpansuratkontrol($request, $nosuratkontrol)
    {
        $simpansuratkontrol = Simpansuratkontrol::firstOrCreate(
            [
                'noSuratKontrol' => $nosuratkontrol
            ],
            [
                'noreg' => $request->noreg,
                'norm' => $request->noreg,
                'kodeDokter' => $request->kddokter,
                'poliKontrol' => $request->kodepolibpjs,
                'tglRencanaKontrol' => $request->tglrencanakunjungan,
                'namaDokter' => $request->dokter,
                'noKartu' => $request->noka,
                'nama' => $request->nama,
                'kelamin' => $request->kelamin,
                'tglLahir' => $request->tgllahir,
                'user_id' => auth()->user()->pegawai_id
            ]
        );
        if (!$simpansuratkontrol) {
            return 500;
        }
        return 200;
    }
}
