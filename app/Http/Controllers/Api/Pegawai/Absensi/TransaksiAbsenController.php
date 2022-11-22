<?php

namespace App\Http\Controllers\Api\Pegawai\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class TransaksiAbsenController extends Controller
{
    //
    public function getRekapByUser()
    {
        $user = JWTAuth::user();
        $thisYear = date('Y');
        $per_page = request('per_page') ? request('per_page') : 10;
        $data = TransaksiAbsen::where('user_id', $user->id)
            ->whereDate('tanggal', '>=', $thisYear . '-01-01')
            ->whereDate('tanggal', '<=', $thisYear . '-12-31')
            ->paginate($per_page);

        return new JsonResponse($data);
    }
    public function getRekapPerUser()
    {
        $user = User::find(request('id'));
        $thisYear = date('Y');
        $month = request('month');
        // $per_page = request('per_page') ? request('per_page') : 10;
        $data = TransaksiAbsen::where('user_id', $user->id)
            ->whereDate('tanggal', '>=', $thisYear . '-' . $month . '-01')
            ->whereDate('tanggal', '<=', $thisYear . '-' . $month . '-31')
            ->orderBy(request('order_by'), request('sort'))
            ->get();
        $collects = collect($data);
        $tanggals = [];
        foreach ($data as $key) {
            $temp = date('Y/m/d', strtotime($key['tanggal']));
            array_push($tanggals, $temp);
        };
        return new JsonResponse([
            'tanggals' => $tanggals,
            'data' => $data,
        ], 200);
    }
}
