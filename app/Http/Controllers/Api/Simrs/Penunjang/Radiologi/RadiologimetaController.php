<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Radiologi;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Radiologi\Mjenispemeriksaanradiologimeta;
use App\Models\Simrs\Penunjang\Radiologi\Mpemeriksaanradiologi;
use App\Models\Simrs\Penunjang\Radiologi\Mpemeriksaanradiologimeta;
use Illuminate\Http\JsonResponse;

class RadiologimetaController extends Controller
{
    public function listmasterpemeriksaanradiologi()
    {
        $listmasterpemeriksaanradiologi = Mpemeriksaanradiologimeta::get();
        return new JsonResponse($listmasterpemeriksaanradiologi);
    }

    public function jenispermintaanradiologi()
    {
        $jenispermintaanradiologi = Mjenispemeriksaanradiologimeta::all();
        return new JsonResponse($jenispermintaanradiologi);
    }

    public function listpermintaanradiologirinci()
    {
        $rincianpermintaan = Mpemeriksaanradiologi::all();
        return new JsonResponse($rincianpermintaan);
    }
}
