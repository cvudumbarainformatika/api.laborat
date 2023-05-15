<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Rajalumum;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DaftarrajalbpjsController extends Controller
{
    public function simpandaftarbpjs(Request $request)
    {
        try {
            $masterpasien =DaftarrajalumumController::simpanMpasien($request);
            return ($masterpasien);

        } catch (\Exception $th){

        }
    }
}
