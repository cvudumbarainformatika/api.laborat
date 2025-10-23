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
                    'user',
                    'caripeminjaman' => function ($q) {
                        $q->select('tpeminjaman_h.*')
                            ->whereNull('tgl_kembali');
                    }
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
        try {
            $data = Pegawai::select('nip', 'nama')->where('aktif','AKTIF')->get();
            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Data gagal disimpan',
                'error' => $e->getMessage()
            ], 500);
        }
        // $data = Pegawai::select('nip', 'nama')->where('aktif','AKTIF')->get();
        // return new JsonResponse($data);
    }

    public function simpanpeminjaman(Request $request)
    {

        $user = FormatingHelper::session_user();
        $kdpegsimrs = $user['kodesimrs'];


        try {
            DB::beginTransaction();

            $rincian = $request->rincian[0];

            $cekx = PeminjamanHeder::where('noarsip', $rincian['noarsip'])->whereNull('tgl_kembali')->count();
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
                    'rencana_kembali' => $request->rencanakembali,
                    'peminjam' => $request->peminjam,
                    'keperluan' => $request->keterangan,
                    'petugas' => $kdpegsimrs,
                    'unitpengolah' => $request->unitpengolah,
                    'noarsip' => $rincian['noarsip'],
                    'flagmap' => $rincian['posisiarsip'],
                    'flag' => $rincian['mapstatus'],
                    'posisifelingkabinet' => $rincian['posisifellingkabinet'],
                    'laci' => $rincian['laci'],
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
                    'user',
                    'caripeminjaman' => function ($q) {
                        $q->select('tpeminjaman_h.*')
                            ->whereNull('tgl_kembali');
                    }
                ]
            )
            ->where('data_arsip.noarsip', $noarsip)
            ->first();
        return $data;
    }

    public function getlistpeminjaman()
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

        $data = PeminjamanHeder::select(
            'tpeminjaman_h.*',
            'master_lokasi.nama_lokasi',
            'master_media.nama_media',
            'master_kode.kode as kodeklasifikasi',
            'master_kode.nama as namakelasifikasi',
            'master_fillingcabinet.namacabinet as namacabinet',
        )
            ->join('data_arsip', 'data_arsip.noarsip', 'tpeminjaman_h.noarsip')
            ->join('master_kode', 'data_arsip.kode', 'master_kode.kode')
            ->join('master_lokasi', 'data_arsip.lokasi', 'master_lokasi.id')
            ->join('master_media', 'data_arsip.media', 'master_media.id')
            ->leftjoin('master_fillingcabinet', 'tpeminjaman_h.posisifelingkabinet', 'master_fillingcabinet.id')
             ->where(function ($query) {
                $query->where('data_arsip.noarsip', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('data_arsip.uraian', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('master_kode.kode', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('master_kode.nama', 'LIKE', '%' . request('q') . '%');
            })->with([
                'user:kdpegsimrs,nama,nip,nik',
                'userpeminjam:kdpegsimrs,nama,nip,nik',
                'unitpengolahx:kode,nama',
            ])
            ->whereIn('data_arsip.unit_pengolah', $bidangbagian)
            ->orderBy('tpeminjaman_h.tgl', 'desc')
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }

    public function simpankembali(Request $request)
    {
        $user = FormatingHelper::session_user();
        $kdpegsimrs = $user['kodesimrs'];
        try {
            DB::beginTransaction();
            $pinjam = PeminjamanHeder::where('notrans', $request->nopeminjaman)->first();

            if(!$pinjam) {
                return new JsonResponse(['message' => 'Data Tidak ditemukan'], 500);
            }
            $pinjam->update([
                'tgl_kembali' => date('Y-m-d'),
                'petugas_penerimna' => $kdpegsimrs,
                'keterangan_waktukembali' => $request->keterangan,
            ]);

            DB::commit();
            $data = self::getdataarsip($request->noarsip);
            return new JsonResponse(['message' => 'success', 'result' => $data], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return new JsonResponse([
                'message' => 'Data gagal disimpan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function caripegawai()
    {
        try {
            $data = Pegawai::select('nik', 'nama')->where('aktif','AKTIF')->where('nama', 'LIKE', '%' . request('q') . '%')->limit(25)->get();
            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Data gagal disimpan',
                'error' => $e->getMessage()
            ], 500);
        }
        // $data = Pegawai::select('nip', 'nama')->where('aktif','AKTIF')->get();
        // return new JsonResponse($data);
    }
}
