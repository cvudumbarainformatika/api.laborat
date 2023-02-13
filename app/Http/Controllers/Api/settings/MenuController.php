<?php

namespace App\Http\Controllers\Api\settings;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Pegawai\Akses\Aplikasi;
use App\Models\Submenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{

    public function index()
    {
        $data = Menu::with('submenu')->get();

        return new JsonResponse($data);
    }

    public function aplikasi()
    {
        $data = Aplikasi::with(['menus.submenus'])->get();
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $saved = null;
        if ($request->has('id')) {
            $saved = Menu::find($request->id)->update(['nama' => $request->nama]);
        } else {
            $saved = Menu::create(['nama' => $request->nama]);
        }

        if (!$saved) {
            return new JsonResponse(['message' => 'Maaf data tidak tersimpan, Error'], 500);
        }
        return new JsonResponse(['message' => 'Success!! data sudah tersimpan'], 201);
    }

    public function store_submenu(Request $request)
    {
        // $saved = null;
        // if ($request->has('id')) {
        //     $saved = Menu::find($request->id)->update(['nama' => $request->nama]);
        // } else {
        //     $saved = Menu::create(['nama' => $request->nama]);
        // }

        $saved = Submenu::updateOrCreate(
            ['id' => $request->id, 'menu_id' => $request->menu_id],
            ['nama' => $request->nama]
        );

        if (!$saved) {
            return new JsonResponse(['message' => 'Maaf data tidak tersimpan, Error'], 500);
        }
        return new JsonResponse(['message' => 'Success!! data sudah tersimpan'], 201);
    }

    public function delete(Request $request)
    {
        $data = Menu::find($request->id);
        $data->submenu()->delete();
        $del = $data->delete();
        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }
        // $user->log("Menghapus Data Jabatan {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
    public function delete_submenu(Request $request)
    {
        $data = Submenu::find($request->id);
        $del = $data->delete();
        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }
        // $user->log("Menghapus Data Jabatan {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
