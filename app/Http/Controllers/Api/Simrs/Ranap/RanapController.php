<?php

namespace App\Http\Controllers\Api\Simrs\Ranap;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RanapController extends Controller
{
    public function kunjunganpasien()
    {
        $ruangan = request('koderuangan');

        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('to') . ' 00:00:00';
            $tglx = request('from') . ' 23:59:59';
        }
        $data = Kunjunganranap::whereBetween('rs17.rs3', [$tgl, $tglx])->get();
    }
}
