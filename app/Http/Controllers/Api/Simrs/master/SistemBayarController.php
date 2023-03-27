<?php

namespace App\Http\Controllers;

use App\Models\Msistembayar;
use Illuminate\Http\JsonResponse;


class SistemBayar_ar extends Controller
{
    public function index()
    {
        $data = Msistembayar::all();
        return new JsonResponse();
    }

}
