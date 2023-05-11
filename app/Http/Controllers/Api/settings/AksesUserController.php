<?php

namespace App\Http\Controllers\Api\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AksesUserController extends Controller
{
    //
    public function userAkses()
    {
    }
    public function storeAkses(Request $request)
    {
        // $anu = [];
        // foreach ($request->app as $app) {
        //     foreach ($app['menus'] as $menus) {
        //         foreach ($menus['submenus'] as $sub) {
        //             array_push($anu, [$app['id'], $menus['id'], $sub]);
        //         }
        //     }
        // }
        $data = $request->all();
        return new JsonResponse($data);
    }
}
