<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Bast;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BasthederM;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BastController extends Controller
{
    public function dialogsp()
    {
        $sp = PemesananHeder::with(
            [
                'rinci',
                'pihakketiga'
            ]
        )
            ->where('flag', '1')
            ->get();
        return new JsonResponse($sp);
    }

    public function dialogpenerimaan()
    {
        $dialogpenerimaan = PenerimaanHeder::with(
            [
                'penerimaanrinci',
                'penerimaanrinci.masterobat'
            ]
        )
            ->where('kunci', '1')
            ->where('nopemesanan', '!=', '')
            ->get();
        return new JsonResponse($dialogpenerimaan);
    }

    public function simpanbast(Request $request)
    {
        if ($request->nobast === '' || $request->nobast === null) {
            DB::connection('farmasi')->select('call nobast(@nomor)');
            $x = DB::connection('farmasi')->table('conter')->select('bast')->get();
            $wew = $x[0]->bast;
            $nobast = FormatingHelper::penerimaanobat($wew, 'BAST-FAR');
        } else {
            $nobast = $request->nobast;
        }



        $user = FormatingHelper::session_user();
        $simpan = BasthederM::updateorcreate(
            [
                'nobast' => $nobast,
                'tgl' => $request->tgl,
                'nosp' => $request->nosep,
                'penyedia' => $request->penyedia,
                'user' => $user['kodesimrs'],
            ]
        );
        if (!$simpan) {
            return new JsonResponse(['message' => 'BAST Gagal Disimpan...!!!'], 500);
        }
    }
}
