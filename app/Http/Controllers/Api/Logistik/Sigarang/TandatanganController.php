<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Tandatangan;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TandatanganController extends Controller
{
    //
    public function index()
    {
        $user = auth()->user();
        $data = Tandatangan::with('ptk', 'ppk.relasi_jabatan', 'gudang', 'mengetahui')->where('user', $user->id)->first();

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $data = Tandatangan::updateOrCreate(
            ['user' => $user->id],
            $request->all()

        );
        if ($data->wasChanged()) {
            return new JsonResponse([
                'message' => 'Data telah di Update'
            ], 200);
        }
        if ($data->wasRecentlyCreated) {
            return new JsonResponse([
                'message' => 'Data telah di Buat'
            ], 201);
        }
        return new JsonResponse([
            'message' => 'tidak ada perubahan data'
        ], 410);
    }

    public function getPtk()
    {
        $data = Pegawai::where('aktif', 'AKTIF')
            ->when(request('ptk') ?? function ($wew) {
                // $wew->where('role_id', 2);
                $wew->limit(20);
            }, function ($anu) {
                $anu->where('nama', 'LIKE', '%' . request('ptk') . '%')
                    ->where('role_id', 2);
            })
            ->limit(20)->get();
        return new JsonResponse($data);
    }
    public function getGudang()
    {
        $data =
            Pegawai::where('aktif', 'AKTIF')
            ->when(request('gudang') ?? function ($wew) {
                $wew->where('role_id', 3);
            }, function ($anu) {
                $anu->where('nama', 'LIKE', '%' . request('gudang') . '%')
                    ->where('role_id', 3);
            })
            ->limit(20)->get();
        return new JsonResponse($data);
    }
    public function getMengetahui()
    {
        $data =
            Pegawai::where('aktif', 'AKTIF')
            ->when(request('tahu') ?? function ($wew) {
                $wew->limit(20);
            }, function ($anu) {
                $anu->where('nama', 'LIKE', '%' . request('tahu') . '%')
                    ->limit(20);
            })
            ->get();
        return new JsonResponse($data);
    }
    public function getPpk()
    {
        $data =
            Pegawai::where('aktif', 'AKTIF')
            ->when(request('ppk') ?? function ($wew) {
                $wew->limit(20);
            }, function ($anu) {
                $anu->where('nama', 'LIKE', '%' . request('ppk') . '%')
                    ->limit(20);
            })
            ->get();
        return new JsonResponse($data);
    }
}
