<?php

namespace App\Http\Controllers\Api\Simrs\UnitPelayananArsip;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\MorganisasiAdministrasi;
use App\Models\Simrs\UnitPengelolahArsip\Dataarsip;
use App\Models\Simrs\UnitPengelolahArsip\MapHeder;
use App\Models\Simrs\UnitPengelolahArsip\MapRincian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataMapController extends Controller
{
    // public function listdata()
    // {

    //     if (request('bidangbagian') === '' || request('bidangbagian') === null) {
    //         $organisasi = MorganisasiAdministrasi::select('kode')->where('hiddenx', '')->get();
    //         $raw = collect($organisasi);
    //         $only = $raw->map(function ($y) {
    //             return $y->kode;
    //         });
    //         $bidangbagian = $only;
    //     } else {
    //         $bidangbagian = array(request('bidangbagian'));
    //     }
    //     $data = MapHeder::whereIn('kodeorganisasi', $bidangbagian)
    //     ->with(['klasifikasi','unitpengolah','kabinet'])
    //     ->when(request('q'), function ($q) {
    //         $q->where('namamap', 'LIKE', '%' . request('q') . '%');
    //     })
    //      ->paginate(10);
    //     return new JsonResponse($data);
    // }

    public function listdata()
    {

        if (empty(request('bidangbagian'))) {
            // AMBIL SEMUA ORGANISASI AKTIF
            $bidangbagian = MorganisasiAdministrasi::where(function ($query) {
                    $query->whereNull('hiddenx')
                        ->orWhere('hiddenx', '')
                        ->orWhere('hiddenx', '0');
                })
                ->pluck('kode')
                ->toArray();
        } else {
            $bidangbagian = [request('bidangbagian')];
        }

        $data = MapHeder::whereIn('kelompokMap_H.kodeorganisasi', $bidangbagian)
            ->with(['unitpengolah', 'kabinet'])
            ->join('master_kode', 'kelompokMap_H.kodeklasifikasi', '=', 'master_kode.kode')
            ->select([
                'kelompokMap_H.*',
                'master_kode.nama as keterangan_kode',
                'master_kode.kode as kode_master',
                'master_kode.retensi as retensi',
            ])
            ->selectRaw('
                    YEAR(CURDATE()) - kelompokMap_H.tahunMap as umur_berkas,
                    CASE
                        WHEN (YEAR(CURDATE()) - kelompokMap_H.tahunMap) < master_kode.retensi
                        THEN "AKTIF"
                        ELSE "INAKTIF"
                    END as status_arsip
                ')
            ->where('kelompokMap_H.tahunMap', request('tahunmap'))
            ->where(function ($query) {
                $query->where('kelompokMap_H.namamap', 'like', '%'.request('q').'%')
                ->orWhere('master_kode.nama', 'like', '%'.request('q').'%')
                ->orWhere('kelompokMap_H.kodeklasifikasi', 'like', '%'.request('q').'%')
                ->orWhere('kelompokMap_H.keterangan', 'like', '%'.request('q').'%')
                ->orWhere('kelompokMap_H.laci', 'like', '%'.request('q').'%');
            })
            ->paginate(request('per_page'));

        return response()->json($data);
        }


    public function simpanmap(Request $request)
    {
        try {
            DB::beginTransaction();
                $user = FormatingHelper::session_user();
                $kdpegsimrs = $user['kodesimrs'];
                $simpan = MapHeder::updateOrCreate(
                    [
                        'id' => $request->id
                    ],
                    [
                        'namamap' => $request->namamap,
                        'kodeorganisasi' => $request->kodeorganisasi,
                        'kodeklasifikasi' => $request->kodekelasifikasi,
                        'keterangan' => $request->keterangan,
                        'user'=> $kdpegsimrs,
                        'kodefelingkabinet' => $request->cabinet,
                        'laci' => $request->laci,
                         'tahunMap' => $request->tahunmap,
                    ]
                );
            DB::commit();
                if($request->kodeorganisasi === '' || $request->kodeorganisasi === null){
                    $organisasi = MorganisasiAdministrasi::select('kode')->where('hiddenx', '')->get();
                    $raw = collect($organisasi);
                    $only = $raw->map(function ($y) {
                        return $y->kode;
                    });
                    $bidangbagian = $only;
                }else{
                    $bidangbagian = array($request->kodeorganisasi);
                }
                $data = self::responsimpan($bidangbagian, $request->tahunmap);
                return new JsonResponse(['message' => 'Data Berhasil Disimpan', 'result' => $data],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['message' => 'Data Gagal Disimpan', 'error' => $e], 500);
        }
    }

    public static  function responsimpan($bidangbagian, $tahunmap){
        $data = MapHeder::select('kelompokMap_H.*', 'master_kode.nama as keterangan_kode',
                'master_kode.kode as kode_master')
            ->with(['unitpengolah', 'kabinet'])
            ->join('master_kode', 'kelompokMap_H.kodeklasifikasi', '=', 'master_kode.kode')
            ->whereIn('kodeorganisasi', $bidangbagian)->where('tahunMap', $tahunmap)
            ->get();
        return $data;
    }

    public function simpanisimap(Request $request)
    {
        try {
            DB::beginTransaction();
                $user = FormatingHelper::session_user();
                $kdpegsimrs = $user['kodesimrs'];
                $simpan = MapRincian::updateOrCreate(
                    [
                        'id_heder' => $request->idmap,
                        'noarsip' => $request->noarsip,
                        'users'=> $kdpegsimrs
                    ]
                );
                $update = Dataarsip::where('noarsip', $request->noarsip)->first();
                $update->flagmap = 1;
                $update->save();
            DB::commit();
                $data = MapHeder::with(
                    [
                        'rinciandalammap' => function ($x) {
                            $x->with(
                                [
                                    'dataarsip' => function ($q) {
                                        $q->with(['media','user']);
                                    }
                                ]);
                        },
                        'klasifikasi',
                        'unitpengolah',
                        'kabinet'
                    ]
                )
        ->where('id', request('idmap'))->get();
                return new JsonResponse(
                    ['message' => 'Data Berhasil Disimpan','result' => $request->noarsip,'rincianmap' => $data,'idmap' => $request->idmap],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['message' => 'Data Gagal Disimpan', 'error' => $e], 500);
        }
    }

    public function rinciandidalammap()
    {
        $data = MapHeder::with(
            [
                'rinciandalammap' => function ($x) {
                    $x->with(
                        [
                            'dataarsip' => function ($q) {
                                $q->with(['media','user']);
                            }
                        ]);
                },
                'klasifikasi',
                'unitpengolah',
                'kabinet'
            ]
        )
        ->where('id', request('idmap'))->get();
        return new JsonResponse($data);
    }

    public function hapusrinciandalammap(Request $request)
    {

        try {
            DB::beginTransaction();
                $user = FormatingHelper::session_user();
                $kdpegsimrs = $user['kodesimrs'];
                $hapus = MapRincian::where('id', $request->id)->delete();
                $update = Dataarsip::where('noarsip', $request->noarsip)->first();
                $update->flagmap = '';
                $update->save();
            DB::commit();
                return new JsonResponse(['message' => 'Data Berhasil Dihapus', 'result' => $update],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['message' => 'Data Gagal Dihapus', 'error' => $e], 500);
        }
    }

    public function hapusmap(Request $request)
    {
        $cek = MapRincian::where('id_heder', $request->id)->count();
        if($cek > 0){
            return new JsonResponse(['message' => 'Data Tidak Bisa Dihapus, Map Ini Memiliki Rincian Data'], 500);
        }
        try {
            DB::beginTransaction();
                $user = FormatingHelper::session_user();
                $kdpegsimrs = $user['kodesimrs'];
                $hapus = MapHeder::where('id', $request->id)->delete();
            DB::commit();
                return new JsonResponse(['message' => 'Data Berhasil Dihapus'],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['message' => 'Data Gagal Dihapus', 'error' => $e], 500);
        }
    }

}
