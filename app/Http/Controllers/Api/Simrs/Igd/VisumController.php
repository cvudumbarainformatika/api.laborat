<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\Igd\Visum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisumController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        $request->validate(['noreg' => 'required|string']);

        return new JsonResponse([
            'data' => Visum::where('noreg', $request->noreg)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function hapus(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'noreg' => 'required|string',
        ]);

        $visum = Visum::where('id', $request->id)
            ->where('noreg', $request->noreg)
            ->firstOrFail();

        $visum->delete();

        return new JsonResponse(['message' => 'Data visum berhasil dihapus.']);
    }

    public function simpan(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'nullable|integer|exists:visum,id',
            'noreg' => 'required|string|max:255',
            'norm' => 'nullable|string|max:255',
            'dpjp' => 'nullable|string|max:255',
            'nomor' => 'nullable|string|max:255',
            'novisum' => 'nullable|string|max:255',
            'tanggalvisum' => 'nullable|date',
            'jam' => 'nullable|date_format:H:i,H:i:s',
            'permintaan' => 'nullable|string',
            'dari' => 'nullable|string|max:255',
            'tanggalsurat' => 'nullable|date',
            'nosurat' => 'nullable|string|max:255',
            'bangsa' => 'nullable|string|max:255',
            'umur' => 'nullable|string|max:100',
            'pekerjaan' => 'nullable|string|max:255',
            'Alamat' => 'nullable|string',
            'alamat' => 'nullable|string',
        ]);

        $dpjp = $request->input('dpjp') ?: DB::table('rs17')
            ->leftJoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs17.rs9')
            ->where('rs17.rs1', $request->noreg)
            ->selectRaw("COALESCE(NULLIF(kepegx.pegawai.nama, ''), rs17.rs9) as dpjp")
            ->value('dpjp');

        $jam = $request->input('jam');
        if ($jam && preg_match('/^\d{2}:\d{2}$/', $jam)) {
            $jam .= ':00';
        }

        $data = [
            'noreg' => $request->noreg,
            'norm' => $request->input('norm'),
            'dpjp' => $dpjp,
            'novisum' => $request->input('novisum', $request->input('nomor')),
            'tgl_visum' => $request->input('tanggalvisum') ?: null,
            'jam' => $jam ?: null,
            'permintaan' => $request->input('permintaan'),
            'dari' => $request->input('dari'),
            'tgl_surat' => $request->input('tanggalsurat') ?: null,
            'nomorsurat' => $request->input('nosurat'),
            'bangsa' => $request->input('bangsa'),
            'umur' => $request->input('umur'),
            'pekerjaan' => $request->input('pekerjaan'),
            'alamat' => $request->input('alamat', $request->input('Alamat')),
        ];

        if ($request->filled('id')) {
            $visum = Visum::findOrFail($request->id);
            $visum->update($data);
        } else {
            $visum = Visum::create($data);
        }

        return new JsonResponse([
            'message' => 'Data visum berhasil disimpan.',
            'data' => $visum,
        ]);
    }
}
