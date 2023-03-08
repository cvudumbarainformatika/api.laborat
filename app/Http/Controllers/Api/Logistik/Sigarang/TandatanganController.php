<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Tandatangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TandatanganController extends Controller
{
    //
    public function index()
    {
        $user = auth()->user();
        $data = Tandatangan::with('ptk', 'gudang', 'mengetahui')->where('user_id', $user->id)->first();

        return new JsonResponse($data);
    }
}
