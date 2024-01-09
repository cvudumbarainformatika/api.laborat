<?php

namespace App\Http\Controllers\Api\Satusehat;

use App\Helpers\BridgingSatsetHelper;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Extra;
use App\Models\Pegawai\Ruangan;
use App\Models\Sigarang\Ruang;
use App\Models\Simrs\Organisasi\Organisasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use function PHPUnit\Framework\isEmpty;

class LocationController extends Controller
{
    public function listRuanganRajal()
    {
        $data = Ruang::where('group', '=', 'rajal')->get();

        return response()->json($data);
    }
}
