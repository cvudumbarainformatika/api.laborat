<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Diagnosa;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Diagnosa_m;
use Illuminate\Http\Request;

class ListdiagnosaController extends Controller
{
    public function listdiagnosa()
    {
        $listdiagnosa = Diagnosa_m::select('');
    }
}
