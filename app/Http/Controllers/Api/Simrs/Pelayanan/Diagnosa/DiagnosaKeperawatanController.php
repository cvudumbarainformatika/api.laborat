<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Diagnosa;

use App\Http\Controllers\Api\Simrs\Bridgingeklaim\EwseklaimController;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mdiagnosakeperawatan;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosaKeperawatanController extends Controller
{
    public function diagnosakeperawatan()
    {
        $listdiagnosa = Mdiagnosakeperawatan::with(['intervensis'])
            ->get();
        return new JsonResponse($listdiagnosa);
    }
}
