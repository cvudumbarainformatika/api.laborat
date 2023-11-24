<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Diagnosa;

use App\Http\Controllers\Api\Simrs\Bridgingeklaim\EwseklaimController;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mdiagnosakeperawatan;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosa;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosakeperawatan;
use App\Models\Simrs\Pelayanan\Intervensikeperawatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiagnosaKeperawatanController extends Controller
{
    public function diagnosakeperawatan()
    {
        $listdiagnosa = Mdiagnosakeperawatan::with(['intervensis'])
            ->get();
        return new JsonResponse($listdiagnosa);
    }

    public function simpandiagnosakeperawatan(Request $request)
    {

        try {
            DB::beginTransaction();

            $thumb = [];
            foreach ($request->diagnosa as $key => $value) {
                $diagnosakeperawatan = Diagnosakeperawatan::create(
                    [
                        'noreg' => $value['noreg'],
                        'norm' => $value['norm'],
                        'kode' => $value['kode'],
                        'nama' => $value['nama'],
                    ]
                );

                foreach ($value['details'] as $key => $det) {
                    Intervensikeperawatan::create([
                        'diagnosakeperawatan_kode' => $diagnosakeperawatan->id,
                        'intervensi_id' => $det['intervensi_id']
                    ]);
                }
                array_push($thumb, $diagnosakeperawatan->id);
            }

            DB::commit();

            $success = Diagnosakeperawatan::whereIn('id', $thumb)->get();

            return new JsonResponse(
                [
                    'message' => 'Data Berhasil disimpan',
                    'result' => $success
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollback();
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $e], 500);
        }
    }

    public function deletediagnosakeperawatan(Request $request)
    {
        try {
            DB::beginTransaction();

            $id = $request->id;

            $target = Diagnosakeperawatan::find($id);

            if (!$target) {
                return new JsonResponse(['message' => 'Data tidak ditemukan'], 500);
            }

            Intervensikeperawatan::where('diagnosakeperawatan_kode', $target->id)->delete();

            // if (!$rel) {
            //     return new JsonResponse(['message' => 'Data Gagal dihapus...!!!'], 500);
            // }

            $target->delete;
            DB::commit();
            return new JsonResponse(
                [
                    'message' => 'Data Berhasil dihapus'
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollback();
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $e], 500);
        }
    }
}
