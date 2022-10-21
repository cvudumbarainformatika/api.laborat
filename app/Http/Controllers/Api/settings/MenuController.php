<?php

namespace App\Http\Controllers\Api\settings;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        $data = Menu::with('submenu')->get();

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $saved = Menu::create([
            'nama' => $request->nama
        ]);

        if (!$saved) {
            return new JsonResponse(['message' => 'Maaf data tidak tersimpan, Error'], 500);
        }
        return new JsonResponse(['message' => 'Success!!!'], 201);
    }
}
