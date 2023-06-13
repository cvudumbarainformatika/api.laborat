<?php

namespace App\Http\Controllers\Api\Mobile\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Extra;
use App\Models\Pegawai\Libur;
use App\Models\Pegawai\TransaksiAbsen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class HistoryMobile extends Controller
{
    public function data()
    {
        // yang berhak mengajukan extra adalah karyawan shift, jadi cari yang shift aja
        $user = JWTAuth::user();
        $thisYear = request('tahun') ? request('tahun') : date('Y');
        $month = request('bulan') ? request('bulan') : date('m');

        $from = $thisYear . '-' . $month . '-01';
        $to = $thisYear . '-' . $month . '-31';

        $masuk = TransaksiAbsen::where('user_id', $user->id)
            // ->whereDate('tanggal', '>=', $thisYear . '-' . $month . '-01')
            // ->whereDate('tanggal', '<=', $thisYear . '-' . $month . '-31')
            ->whereBetween('tanggal', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->with('kategory')
            ->latest()
            ->get();


        $data['masuk'] = $masuk;
        $libur = Libur::where(
            'user_id',
            $user->id
        )
            // ->whereDate('tanggal', '>=', $thisYear . '-' . $month . '-01')
            // ->whereDate('tanggal', '<=', $thisYear . '-' . $month . '-31')
            ->whereBetween('tanggal', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->latest()
            ->get();

        $data['libur'] = $libur; // ini data yang ijin
        return new JsonResponse($data);
    }
}
