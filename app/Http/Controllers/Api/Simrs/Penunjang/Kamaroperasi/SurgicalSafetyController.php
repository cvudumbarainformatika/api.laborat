<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Kamaroperasi\Implant;
use App\Models\Simrs\Penunjang\Kamaroperasi\ImplantSeri;
use App\Models\Simrs\Penunjang\Kamaroperasi\SurgicalSafety;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurgicalSafetyController extends Controller
{
    //
    public function getNakes()
    {
        $data = Petugas::select('id', 'nama', 'kdpegsimrs', 'kdgroupnakes')
            ->where('aktif', 'Aktif')
            ->whereIn('kdgroupnakes', ['1', '2'])
            ->get();
        return new JsonResponse([
            'data' => $data
        ]);
    }
    public function store(Request $request)
    {
        $data = SurgicalSafety::updateOrCreate(
            [
                'noreg' => $request->noreg,
                'nota' => $request->nota,
            ],
            $request->all()

        );


        return new JsonResponse([
            'message' => 'Data sudah disimpan',
            'req' => $request->all(),
            'data' => $data
        ]);
    }
    public function getImplat()
    {
        $data = PersiapanOperasiDistribusi::select(
            'new_masterobat.kd_obat',
            'new_masterobat.nama_obat',
            'persiapan_operasi_distribusis.id as distribusi_id',
            'persiapan_operasi_distribusis.jumlah',
            'persiapan_operasi_distribusis.jumlah_retur',
            'persiapan_operasis.flag',
        )
            ->leftJoin('new_masterobat', 'new_masterobat.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat')
            ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
            ->where('new_masterobat.jenis_perbekalan', 'Alkes Habis Pakai')
            ->where('persiapan_operasis.noreg', request('noreg'))
            ->get();
        //
        $implant = Implant::where('noreg', request('noreg'))->where('nota', request('nota'))->get();
        $implantSeri = ImplantSeri::where('noreg', request('noreg'))->where('nota', request('nota'))->get();
        return new JsonResponse([
            'data' => $data,
            'implant' => $implant,
            'implant_seri' => $implantSeri,
            'req' => request()->all(),
        ]);
    }
    public function simpanImplat(Request $request)
    {
        $request->validate([
            'noreg' => 'required',
            'nota' => 'required',
            'data' => 'nullable|array',
        ]);
        $user = FormatingHelper::session_user();
        $toIns = $request->data;
        $data = [];
        try {
            DB::beginTransaction();
            if (empty($toIns)) {
                Implant::where('noreg', $request->noreg)->where('nota', $request->nota)->delete();
            } else {
                // hapus data lama
                $ids = collect($toIns)
                    ->pluck('id')
                    ->filter()   // jaga-jaga kalau ada null
                    ->toArray();
                Implant::where('noreg', $request->noreg)
                    ->where('nota', $request->nota)
                    ->when(
                        count($ids) > 0,
                        fn($q) => $q->whereNotIn('id', $ids)
                    )
                    ->delete();
                foreach ($toIns as $key) {
                    $ins = Implant::updateOrCreate(
                        [
                            'noreg' => $request->noreg,
                            'nota' => $request->nota,
                            'persiapan_operasi_distribusi_id' => $key['distribusi_id'],
                        ],
                        [
                            'kd_obat' => $key['kd_obat'],
                            'seri' => $key['seri'],
                            'jenis' => $key['jenis'],
                            'persediaan_awal' => $key['jumlah'],
                            'pemakaian' => $key['pemakaian'],
                            'sisa' => $key['jumlah_retur'],
                        ]
                    );
                    $ins->update([
                        $ins->wasRecentlyCreated ? 'created_by' : 'updated_by'
                        => $user['kodesimrs']
                    ]);
                    $data[] = $ins;
                }
            }
            DB::commit();
            return new JsonResponse([
                'message' => 'Data berhasil disimpan',
                'data' => $data
                // 'data' => $request->all()
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Data gagal disimpan ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 410);
        }
    }
}
