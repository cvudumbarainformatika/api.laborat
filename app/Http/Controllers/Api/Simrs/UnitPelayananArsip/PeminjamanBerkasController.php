<?php

namespace App\Http\Controllers\Api\Simrs\UnitPelayananArsip;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\MorganisasiAdministrasi;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\UnitPengelolahArsip\Dataarsip;
use App\Models\Simrs\UnitPengelolahArsip\PeminjamanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamanBerkasController extends Controller
{
    public function cariarsip()
    {
        if (request('bidangbagian') === '' || request('bidangbagian') === null) {
            $organisasi = MorganisasiAdministrasi::select('kode')->where('hiddenx', '')->get();
            $raw = collect($organisasi);
            $only = $raw->map(function ($y) {
                return $y->kode;
            });
            $bidangbagian = $only;
        } else {
            $bidangbagian = array(request('bidangbagian'));
        }
        $data = Dataarsip::select(
            'data_arsip.*',
            'master_kode.kode as kodeklasifikasi',
            'master_kode.nama as namakelasifikasi',
            'master_lokasi.nama_lokasi',
            'master_media.nama_media',
            'tpeminjaman_h.notrans'
        )
            ->join('master_kode', 'data_arsip.kode', 'master_kode.kode')
            ->join('master_lokasi', 'data_arsip.lokasi', 'master_lokasi.id')
            ->join('master_media', 'data_arsip.media', 'master_media.id')
            ->leftJoin('tpeminjaman_h', 'data_arsip.noarsip', 'tpeminjaman_h.noarsip')
            ->with(
                [
                    'unitpengolah',
                    'rincianmap' => function ($q) {
                        $q->select('kelompokMap_R.*', 'kelompokMap_H.*','master_fillingcabinet.*')
                         ->join('kelompokMap_H', 'kelompokMap_H.id', 'kelompokMap_R.id_heder')
                         ->join('master_fillingcabinet', 'master_fillingcabinet.id', 'kelompokMap_H.kodefelingkabinet');
                    },
                    'user'
                ]
            )
            ->when(request('deskripsi'), function ($q) {
                $q->where('data_arsip.uraian', 'LIKE', '%' . request('deskripsi') . '%');
            })
            ->when(request('noarsip'), function ($q) {
                $q->where('data_arsip.noarsip', 'LIKE', '%' . request('noarsip') . '%');
            })
            ->when(request('kodekelasifikasi'), function ($q) {
                $q->where(function ($sub) {
                    $sub->where('master_kode.kode', 'LIKE', '%' . request('kodekelasifikasi') . '%');
                });
            })
            ->whereIn('data_arsip.unit_pengolah', $bidangbagian)
            // ->where('data_arsip.flagmap', '')
            ->get();
        return new JsonResponse($data);
    }

    public function getdatapegawai()
    {

        $data = Pegawai::select('nip', 'nama')->where('aktif','AKTIF')->get();
        return new JsonResponse($data);
    }

    public function simpanpeminjaman(Request $request)
    {

        $user = FormatingHelper::session_user();
        $kdpegsimrs = $user['kodesimrs'];


        try {
            DB::beginTransaction();

            $rincian = $request->rincian[0];

            $cekx = PeminjamanHeder::where('noarsip', $rincian['noarsip'])->count();
            if ($cekx > 0) {
                return new JsonResponse(['message' => 'Berkas/Arsip masih dalam proses peminjaman dan belum di kembalikan...!!!'], 400);
            }

            $cek = MorganisasiAdministrasi::where('kode', $request->unitpengolah)->first();
            if($request->nopeminjaman === null || $request->nopeminjaman === '') {
                $notrans = $cek->panggilan . '-' . date('YmdHis');
            } else {
                $notrans = $request->nopeminjaman;
            }

            $pinjam = PeminjamanHeder::firstOrCreate(
                ['notrans' => $notrans],
                [
                    'unitpengolah' => $request->unitpengolah,
                    'tgl' => $request->tanggal,
                    'rencana_kembali' => $request->rencana_kembali,
                    'peminjam' => $request->peminjam,
                    'keperluan' => $request->keterangan,
                    'petugas' => $kdpegsimrs,
                    'unitpengolah' => $request->unitpengolah,
                    'noarsip' => $rincian['noarsip'],
                    'flagmap' => $rincian['posisiarsip'],
                    'flag' => $rincian['mapstatus'],
                    'posisifelingkabinet' => $rincian['laci'],
                    'posisimap' => $rincian['map'], // pastikan ini string, bukan array
                ]
            );

            DB::commit();
            $data = self::getdataarsip($rincian['noarsip']);
            return new JsonResponse(['message' => 'success', 'result' => $data], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return new JsonResponse([
                'message' => 'Data gagal disimpan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public static function getdataarsip($noarsip)
    {
        $data = Dataarsip::select(
            'data_arsip.*',
            'master_kode.kode as kodeklasifikasi',
            'master_kode.nama as namakelasifikasi',
            'master_lokasi.nama_lokasi',
            'master_media.nama_media',
            'tpeminjaman_h.notrans'
        )
            ->join('master_kode', 'data_arsip.kode', 'master_kode.kode')
            ->join('master_lokasi', 'data_arsip.lokasi', 'master_lokasi.id')
            ->join('master_media', 'data_arsip.media', 'master_media.id')
            ->leftJoin('tpeminjaman_h', 'data_arsip.noarsip', 'tpeminjaman_h.noarsip')
            ->with(
                [
                    'unitpengolah',
                    'rincianmap' => function ($q) {
                        $q->select('kelompokMap_R.*', 'kelompokMap_H.*','master_fillingcabinet.*')
                         ->join('kelompokMap_H', 'kelompokMap_H.id', 'kelompokMap_R.id_heder')
                         ->join('master_fillingcabinet', 'master_fillingcabinet.id', 'kelompokMap_H.kodefelingkabinet');
                    },
                    'user'
                ]
            )
            ->where('data_arsip.noarsip', $noarsip)
            ->first();
        return $data;
    }
}
