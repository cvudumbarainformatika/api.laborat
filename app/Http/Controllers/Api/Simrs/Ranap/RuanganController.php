<?php

namespace App\Http\Controllers\Api\Simrs\Ranap;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Ranap\Mruangranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RuanganController extends Controller
{
    public function listruanganranap()
    {
        $list = Mruangranap::select('groups', 'groups_nama')
            ->groupby('groups')
            ->where('hiddens', '')
            ->get();
        return new JsonResponse($list);
    }
}
