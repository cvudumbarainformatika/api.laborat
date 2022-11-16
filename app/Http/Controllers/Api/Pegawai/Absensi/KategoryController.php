<?php

namespace App\Http\Controllers\Api\Pegawai\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Kategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KategoryController extends Controller
{
    public function index()
    {
        $data = Kategory::oldest('id')
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
}
