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
            ->with('kategory')
            ->get();
        $tanggals = [];
        foreach ($data as $key) {
            $temp = date('Y/m/d', strtotime($key['tanggal']));
            $week = date('W', strtotime($key['tanggal']));
            $toIn = explode(':', $key['kategory']->masuk);
            $act = explode(':', $key['masuk']);
            $jam = (int)$act[0] - (int)$toIn[0];
            $menit =  (int)$act[1] - (int)$toIn[1];
            $detik =  (int)$act[2] - (int)$toIn[2];

            // $key['jam'] = $jam;
            // $key['menit'] = $menit;
            // $key['detik'] = $detik;
            if ($jam > 0 || $menit > 10) {
                $key['terlambat'] = 'yes';
            } else {
                $key['terlambat'] = 'no';
            }
            $dMenit = $menit >= 10 ? $menit : '0' . $menit;
            $dDetik = $detik >= 10 ? $detik : '0' . $detik;
            $diff = $jam . ':' . $dMenit . ':' . $dDetik;
            $key['diff'] = $diff;
            $key['week'] = $week;
            array_push($tanggals, $temp);
        };
        $collects = collect($data);
        $grouped = $collects->groupBy('week');
        $telat = $collects->where('terlambat', 'yes')->count();
        return new JsonResponse([
            'telat' => $telat,
            'weeks' => $grouped,
            'tanggals' => $tanggals,
            'data' => $data,
        ], 200);
    }
}
