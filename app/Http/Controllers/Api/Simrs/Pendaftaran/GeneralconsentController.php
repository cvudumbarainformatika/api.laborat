<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Mgeneralconsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeneralconsentController extends Controller
{
    public function simpangeneralcontent(Request $request)
    {
        $request->validate([
        'noreg' => 'required|unique:generalconsent,noreg'
        ]);
        $simpangeneralcontent = Mgeneralconsent::create($request->all);

        if(!$simpangeneralcontent)
        {
            return new JsonResponse(['message => Data Gagal Disimpan'], 500);
        }
        return new JsonResponse(['message => Data Sudah Disimpan'], 200);
    }
}
