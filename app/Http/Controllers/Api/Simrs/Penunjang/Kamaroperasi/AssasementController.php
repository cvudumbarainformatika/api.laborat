<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Laporan\Operasi\PermintaanOperasi;
use App\Models\Simrs\Penunjang\Kamaroperasi\AssasemenPraBedah;
use App\Models\Simrs\Penunjang\Kamaroperasi\AssasmentPraInduksi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssasementController extends Controller
{
    public function getnota()
    {
        $nota = PermintaanOperasi::select('rs2 as nota')->where('rs1', request('noreg'))
            ->where('rs10', request('ruang'))
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }
    public function ambil()
    {
        $data = AssasemenPraBedah::where('nota', request('nota'))->first();
        return new JsonResponse($data);
    }
    // pra pra_bedah
    public function simpan(Request $request)
    {
        $request->validate(
            [
                'noreg' => 'required',
                'nota' => 'required',
                'norm' => 'required',
            ]
        );
        $inputData = $request->except(['_token', '_method']);
        $user = FormatingHelper::session_user();
        $inputData['user_input'] = $user['kodesimrs'];

        $data = AssasemenPraBedah::updateOrCreate(
            [
                'noreg' => $request->noreg,
                'nota' => $request->nota,
                'norm' => $request->norm,
            ],
            $inputData
        );

        return new JsonResponse([
            'message' => 'Data Berhasil Disimpan',
            'data' => $data,
            // 'req' => $request->all()
        ]);
    }
    public function simpanPraInduksi(Request $request)
    {
        $request->validate(
            [
                'noreg' => 'required',
                'nota' => 'required',
                'norm' => 'required',
            ]
        );
        $data = AssasmentPraInduksi::updateOrCreate(
            [
                'noreg' => $request->noreg,
                'nota' => $request->nota,
                'norm' => $request->norm,
            ],
            $request->all()
        );

        return new JsonResponse([
            'message' => 'Data Berhasil Disimpan',
            'data' => $data,
            'req' => $request->all()
        ]);
    }
}
