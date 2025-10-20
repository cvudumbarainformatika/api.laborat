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
    public function listdata()
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
        $data = MapHeder::whereIn('kodeorganisasi', $bidangbagian)
        ->when(request('q'), function ($q) {
            $q->where('namamap', 'LIKE', '%' . request('q') . '%');
        })
        ->get();
        return new JsonResponse($data);
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
                $data = MapHeder::whereIn('kodeorganisasi', $bidangbagian)->get();
                return new JsonResponse(['message' => 'Data Berhasil Disimpan', 'result' => $data],200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['message' => 'Data Gagal Disimpan', 'error' => $e], 500);
        }
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

}
