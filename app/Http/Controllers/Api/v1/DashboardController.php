<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use App\Models\Kunjungan;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $now = date('Y-m-d');
        $kunjungan = Kunjungan::selectRaw('id')->get()->count();
        $view_hr_ini = Kunjungan::selectRaw('id,ip,agent,created_at')
                    ->whereDate('created_at', $now)->get()->count(); // takmau where date
        $berita = Berita::selectRaw('id')->get()->count();
        $user = User::selectRaw('id')->get()->count();

        return response()->json(
            [
                'kunjungan'=>$kunjungan,
                'view_hr_ini'=>$view_hr_ini,
                'berita'=>$berita,
                'user'=>$user,
            ]
        );
    }
}
