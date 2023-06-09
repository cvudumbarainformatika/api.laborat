<?php

namespace App\Http\Controllers\Api\Aplikasi;

use App\Http\Controllers\Controller;
use App\Models\aplikasi\Aplikasi;
use App\Models\Pegawai\Akses\Access;
use App\Models\Pegawai\Akses\Menu;
use App\Models\Sigarang\Pegawai;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;


class AplikasiController extends Controller
{
    public function index()
    {
        // $data = Aplikasi::with(['menus', 'menus.submenus'])->get();

        $akses = User::with('akses.aplikasi', 'akses.menu', 'akses.submenu')->find(auth()->user()->id);

        return new JsonResponse($akses);
    }
}
