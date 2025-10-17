<?php

namespace App\Http\Controllers\Api\Simrs\UnitPelayananArsip;

use App\Http\Controllers\Controller;
use App\Models\MorganisasiAdministrasi;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\UnitPengelolahArsip\Dataarsip;
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
            'master_media.nama_media'
        )
            ->join('master_kode', 'data_arsip.kode', 'master_kode.kode')
            ->join('master_lokasi', 'data_arsip.lokasi', 'master_lokasi.id')
            ->join('master_media', 'data_arsip.media', 'master_media.id')
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
            ->when(request('nobox'), function ($q) {
                $q->where('data_arsip.nobox', 'LIKE', '%' . request('nobox') . '%');
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

    // public function simpanpeminjaman(Request $request)
    // {
    //      try {
    //         DB::beginTransaction();


    //         $pinjam =
    //     DB::commit();

    //         return new JsonResponse(['message' => 'success', 'result' => $kirim], 200);
    //     } catch (\Exception $e) {
    //         DB::rollback();
    //         return new JsonResponse([
    //             'message' => 'Upload gagal',
    //             'error'   => $e->getMessage()
    //         ], 500);
    //     }
    // }
}
