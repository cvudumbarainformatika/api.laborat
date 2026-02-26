<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Penunjang\Cssd\BarangCssd;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Kamaroperasi\Implant;
use App\Models\Simrs\Penunjang\Kamaroperasi\ImplantSeri;
use App\Models\Simrs\Penunjang\Kamaroperasi\InventarisInstrumen;
use App\Models\Simrs\Penunjang\Kamaroperasi\InventarisKasa;
use App\Models\Simrs\Penunjang\Kamaroperasi\SurgicalSafety;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
    public function simpanGambar(Request $request)
    {
        try {
            DB::beginTransaction();
            if (!$request->hasFile('file')) throw new Exception('Tidak Ada File yang perlu di simpan');
            $file = $request->file('file');
            if (!$file) throw new Exception('File kosong');
            $user = FormatingHelper::session_user();
            // hapus data lama
            $old = ImplantSeri::where('noreg', $request->noreg)
                ->where('nota', $request->nota)
                ->first();

            if ($old && $old->path && Storage::disk('remote')->exists($old->path)) {
                Storage::disk('remote')->delete($old->path);
            }


            $originalname = $file->getClientOriginalName();
            $nota = preg_replace('/[^A-Za-z0-9\-_.]/', '-', $request->nota);
            $ext = $file->getClientOriginalExtension();
            $penamaan = date('YmdHis') . '-xenter-' . $nota . '.' . $ext;
            $path = $file->storeAs('public/dokumen-implant-ok', $penamaan, 'remote');
            // $path = $file->storeAs('public/dokumen-implant-ok', $penamaan);

            $gallery = ImplantSeri::updateOrCreate(
                [
                    'noreg' => $request->noreg,
                    'nota' => $request->nota,

                ],
                [
                    'path' => $path,
                    'url' => 'dokumen-implant-ok/' . $penamaan,
                    'original' => $originalname,

                ]
            );


            $gallery->update([
                $gallery->wasRecentlyCreated ? 'created_by' : 'updated_by'
                => $user['kodesimrs']
            ]);
            DB::commit();
            return new JsonResponse([
                'message' => 'Data berhasil disimpan',
                'data' => $gallery ?? null
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

    public function hapusGambar(Request $request)
    {

        try {
            DB::beginTransaction();
            $data = ImplantSeri::find($request->id);
            if (!$data) throw new Exception('Data tidak ditemukan');
            if ($data && $data->path && Storage::disk('remote')->exists($data->path)) {
                Storage::disk('remote')->delete($data->path);
            }
            $data->delete();

            DB::commit();
            return new JsonResponse([
                'message' => 'Data berhasil dihapus',
                'data' => $data ?? null
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
    public function masterCssd()
    {
        $data = BarangCssd::all();
        return new JsonResponse(['data' => $data]);
    }

    public function simpanInventarisKasa(Request $request)
    {

        // return new JsonResponse($request->all());
        $request->validate(
            [
                'noreg' => 'required',
                'nota' => 'required',
                'kode' => 'required|string',
                'awal'  => 'required|numeric|gt:0',
                'pakai' => 'required|numeric|gt:0',
            ],
            [
                'awal.gt' => 'persediaan awal harus bernilai lebih besar dari 0',
                'pakai.gt' => 'pemakaian harus bernilai lebih besar dari 0',
                'kode.required' => 'Barang Cssd harus di isi',
            ]
        );
        try {
            DB::beginTransaction();

            $user = FormatingHelper::session_user();
            $data = InventarisKasa::updateOrCreate(
                [
                    'noreg' => $request->noreg,
                    'norm' => $request->norm,
                    'nota' => $request->nota,
                    'kode' => $request->kode,
                ],
                [
                    'nama' => $request->nama,
                    'awal' => $request->awal,
                    'pakai' => $request->pakai,
                    'sisa' => $request->sisa,
                    'tambah' => $request->tambah,
                    'akhir' => $request->akhir,
                ]
            );
            $data->update([
                $data->wasRecentlyCreated ? 'created_by' : 'updated_by'
                => $user['kodesimrs']
            ]);
            DB::commit();
            return new JsonResponse([
                'message' => 'Data berhasil disimpan',
                'data' => $data ?? null
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

    public function hapusInventarisKasa(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);
        try {
            DB::beginTransaction();
            $data = InventarisKasa::find($request->id);
            if (!$data) throw new Exception('Data Tidak ditemukan');
            $data->delete();
            DB::commit();
            return new JsonResponse([
                'message' => 'Data berhasil dihapus',
                'data' => $data ?? null
                // 'data' => $request->all()
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Data gagal dihapus ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 410);
        }
    }
    public function simpanInventarisInstrumen(Request $request)
    {

        // return new JsonResponse($request->all());
        $request->validate(
            [
                'data' => 'required|array',
                'data.*.noreg' => 'required',
                'data.*.nota' => 'required',
                'data.*.nama' => 'required|string',
                // 'data.*.awal'  => 'required|numeric|gt:0',
                // 'data.*.pakai' => 'required|numeric|gt:0',
            ],
            [
                // 'data.*.awal.gt' => 'persediaan awal harus bernilai lebih besar dari 0',
                // 'data.*.pakai.gt' => 'pemakaian harus bernilai lebih besar dari 0',
                'data.*.nama.required' => 'Barang Cssd harus di isi',
            ]
        );
        try {
            DB::beginTransaction();

            $user = FormatingHelper::session_user();
            $response = [];
            foreach ($request->data as $key) {

                $data = InventarisInstrumen::updateOrCreate(
                    [
                        'noreg' => $key['noreg'],
                        'norm' => $key['norm'],
                        'nota' => $key['nota'],
                        // 'kode' => $key['kode'],
                        'nama' => $key['nama'],
                    ],
                    [
                        'awal' => $key['awal'],
                        'pakai' => $key['pakai'],
                        'sisa' => $key['sisa'],
                        'tambah' => $key['tambah'],
                        'akhir' => $key['akhir'],
                    ]
                );
                $data->update([
                    $data->wasRecentlyCreated ? 'created_by' : 'updated_by'
                    => $user['kodesimrs']
                ]);
                $response[] = $data;
            }
            DB::commit();
            return new JsonResponse([
                'message' => 'Data berhasil disimpan',
                'data' => $response ?? null
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

    public function hapusInventarisInstrumen(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);
        try {
            DB::beginTransaction();
            $data = InventarisInstrumen::find($request->id);
            if (!$data) throw new Exception('Data Tidak ditemukan');
            $data->delete();
            DB::commit();
            return new JsonResponse([
                'message' => 'Data berhasil dihapus',
                'data' => $data ?? null
                // 'data' => $request->all()
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'Data gagal dihapus ' . $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 410);
        }
    }
}
