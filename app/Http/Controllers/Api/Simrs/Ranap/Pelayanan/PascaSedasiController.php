<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Ranap\Pelayanan\PascaSedasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PascaSedasiController extends Controller
{
    public function get(Request $request)
    {
        $noreg = $request->get('noreg');
        $data = PascaSedasi::where('noreg', $noreg)
            ->with([
                'dokter_anestesi:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto',
                'operator_rel:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto',
                'asisten_rel:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto'
            ])
            ->orderBy('id', 'DESC')
            ->get();

        $hasCol = Schema::hasColumn('pasca_sedasi', 'monitoring_pasca');

        foreach ($data as $item) {
            // Jika kolom monitoring_pasca belum ada di DB, ekstrak dari catatan
            if (!$hasCol && $item->catatan) {
                try {
                    $cDec = json_decode($item->catatan, true);
                    if (is_array($cDec) && isset($cDec['monitoring_pasca'])) {
                        $item->monitoring_pasca = is_array($cDec['monitoring_pasca']) ? json_encode($cDec['monitoring_pasca']) : $cDec['monitoring_pasca'];
                        $item->catatan = $cDec['teks'] ?? null;
                    }
                } catch (\Exception $e) {}
            }
        }

        return new JsonResponse([
            'status' => 'success',
            'data' => $data
        ], 200);
    }

    public function list(Request $request)
    {
        return $this->get($request);
    }

    public function store(Request $request)
    {
        $pegawai = Petugas::find(auth()->user()->pegawai_id ?? null);
        $kdpegsimrs = $pegawai ? $pegawai->kdpegsimrs : null;

        $data = null;
        if ($request->has('id') && $request->id) {
            $data = PascaSedasi::find($request->id);
        }

        if (!$data) {
            $data = new PascaSedasi();
            if ($kdpegsimrs) {
                $data->kddokter = $kdpegsimrs;
            }
        }

        $payload = $request->except(['id', 'created_at', 'updated_at']);

        // Pastikan field berbentuk array/object di-encode JSON string untuk kompatibilitas MySQL legacy
        $jsonFields = ['monitoring', 'monitoring_check', 'checklist', 'checklist_persiapan', 'monitoring_intra', 'monitoring_pasca'];
        foreach ($jsonFields as $field) {
            if (isset($payload[$field]) && (is_array($payload[$field]) || is_object($payload[$field]))) {
                $payload[$field] = json_encode($payload[$field]);
            }
        }

        // Jika kolom monitoring_pasca belum dibuat di DB produksi, simpan secara transparan di kolom catatan
        $hasCol = Schema::hasColumn('pasca_sedasi', 'monitoring_pasca');
        if (!$hasCol && isset($payload['monitoring_pasca'])) {
            $catatanText = $payload['catatan'] ?? $data->catatan ?? '';
            $catatanData = [
                'teks' => $catatanText,
                'monitoring_pasca' => json_decode($payload['monitoring_pasca'], true)
            ];
            $payload['catatan'] = json_encode($catatanData);
            unset($payload['monitoring_pasca']);
        }

        $data->fill($payload);
        if (!$data->tgl) {
            $data->tgl = date('Y-m-d H:i:s');
        }
        $data->save();

        // Decode kembali monitoring_pasca jika disimpan di catatan untuk response langsung
        if (!$hasCol && isset($data->catatan)) {
            try {
                $cDec = json_decode($data->catatan, true);
                if (is_array($cDec) && isset($cDec['monitoring_pasca'])) {
                    $data->monitoring_pasca = is_array($cDec['monitoring_pasca']) ? json_encode($cDec['monitoring_pasca']) : $cDec['monitoring_pasca'];
                    $data->catatan = $cDec['teks'] ?? null;
                }
            } catch (\Exception $e) {}
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Data Status Sedasi Berhasil Disimpan',
            'result' => $data->load([
                'dokter_anestesi:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto',
                'operator_rel:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto',
                'asisten_rel:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto'
            ])
        ], 200);
    }

    public function simpandata(Request $request)
    {
        return $this->store($request);
    }

    public function destroy(Request $request)
    {
        $data = PascaSedasi::find($request->id);
        if (!$data) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 444);
        }
        $data->delete();

        return new JsonResponse([
            'success' => true,
            'message' => 'Data Status Sedasi Berhasil Dihapus'
        ], 200);
    }

    public function hapusdata(Request $request)
    {
        return $this->destroy($request);
    }
}
