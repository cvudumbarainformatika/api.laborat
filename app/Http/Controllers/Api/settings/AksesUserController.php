<?php

namespace App\Http\Controllers\Api\settings;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Akses\AksesUser;
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
        $data = [];
        if ($request->tipe === true) {
            foreach ($request->data as $anu) {
                $wew = $this->createAkses($anu);
                array_push($data, $wew);
            }
        } else {
            foreach ($request->data as $anu) {
                $wew = $this->deleteAkses($anu);
                array_push($data, $wew);
            }
        }
        // $data = $request->all();
        return new JsonResponse($data);
    }
    private function createAkses($uncal)
    {
        // return $uncal;
        $data = AksesUser::firstOrCreate(
            [
                'user_id' => $uncal['user_id'],
                'aplikasi_id' => $uncal['aplikasi_id'],
                'menu_id' => $uncal['menu_id'],
                'submenu_id' => $uncal['submenu_id'],
            ]
        );
        return $data;
    }
    private function deleteAkses($uncal)
    {
        $data = AksesUser::where('user_id', $uncal['user_id'])
            ->where('aplikasi_id', $uncal['aplikasi_id'])
            ->where('menu_id', $uncal['menu_id'])
            ->where('submenu_id', $uncal['submenu_id'])
            ->first();
        if ($data) {
            $data->delete();
            return $data;
        }

        return false;
    }
}
