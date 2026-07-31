<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Keuangan;

use App\Http\Controllers\Controller;
use App\Services\RincianPerunitIgdService;
use App\Services\RincianPerunitPenunjangService;
use App\Services\RincianPerunitRajalService;
use App\Services\RincianPerunitRanapService;
use Illuminate\Http\Request;

class RincianPerunitController extends Controller
{
    public function rincianperunit(Request $request)
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'pelayanan' => ['required', 'integer'],
            'jenisLaporan' => ['nullable', 'integer'],
        ]);

        $pelayanan = (int) $validated['pelayanan'];
        $jenisLaporan = (int) ($validated['jenisLaporan'] ?? 0);
        $from = $validated['from'];
        $to = $validated['to'];

        if ($pelayanan === 1) {
            return response()->json(RincianPerunitRajalService::get($jenisLaporan, $from, $to));
        }

        if ($pelayanan === 2) {
            return response()->json(RincianPerunitIgdService::get($jenisLaporan, $from, $to));
        }

        if ($pelayanan === 3) {
            return response()->json(RincianPerunitRanapService::get($jenisLaporan, $from, $to));
        }

        if ($pelayanan === 4) {
            return response()->json(RincianPerunitPenunjangService::get($jenisLaporan, $from, $to));
        }

        return response()->json([
            'message' => 'Jenis laporan belum tersedia.',
            'Title' => 'Rincian Per Unit',
            'Columns' => [],
            'Total' => 0,
            'sRow' => [],
        ], 422);
    }
}
