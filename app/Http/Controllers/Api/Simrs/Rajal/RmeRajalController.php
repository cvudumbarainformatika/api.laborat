<?php

namespace App\Http\Controllers\Api\Simrs\Rajal;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RmeRajalController extends Controller
{
    public function rmerajal()
    {
        $data = KunjunganPoli::with(
            [
                'anamnesis',
                'pemeriksaanfisik' => function ($p) {
                    $p->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                        ->orderBy('id', 'DESC');
                },
                'diagnosa' => function ($a) {
                    $a->with(['masterdiagnosa'])
                        ->orderBy('id', 'DESC');
                },
            ]
        )->where('rs1', request('noreg'))
            ->get();
        return new JsonResponse($data);
    }
}
