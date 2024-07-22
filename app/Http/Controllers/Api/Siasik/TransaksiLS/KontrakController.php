<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiLS;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\KontrakPengerjaan;

class KontrakController extends Controller
{
    public function listkontrak()
    {
        $data = KontrakPengerjaan::when(request('q'),function ($query) {
            $query->where('nokontrak', 'LIKE', '%' . request('q') . '%')
            ->orWhere('namaperusahaan', 'LIKE', '%' . request('q') . '%')
            ->orWhere('namaperusahaan', 'LIKE', '%' . request('q') . '%')
            ->orWhere('kegiatanblud', 'LIKE', '%' . request('q') . '%');
        })->paginate(request('per_page'));

        return new JsonResponse($data);
    }
}
