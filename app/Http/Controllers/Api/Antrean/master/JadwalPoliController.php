<?php

namespace App\Http\Controllers\Api\Antrean\master;

use App\Http\Controllers\Controller;
use App\Models\Antrean\JadwalPoliCache;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class JadwalPoliController extends Controller
{
    /**
     * Mengambil data jadwal poli dari cache (jadwal_poli_cache)
     */
    public function index(Request $request)
    {
        $startOfWeek = Carbon::now('Asia/Jakarta')->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $endOfWeek = Carbon::now('Asia/Jakarta')->startOfWeek(Carbon::MONDAY)->addDays(6)->format('Y-m-d');

        $tglMulai = $request->query('tgl_mulai', $startOfWeek);
        $tglSelesai = $request->query('tgl_selesai', $endOfWeek);

        $query = JadwalPoliCache::query()
            ->whereBetween('tanggal', [$tglMulai, $tglSelesai]);

        if ($request->has('kode_poli') && !empty($request->kode_poli)) {
            $query->where('kode_poli', $request->kode_poli);
        }

        if ($request->has('q') && !empty($request->q)) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('nama_dokter', 'LIKE', "%{$q}%")
                    ->orWhere('nama_poli', 'LIKE', "%{$q}%")
                    ->orWhere('kode_poli', 'LIKE', "%{$q}%");
            });
        }

        $data = $query->orderBy('tanggal', 'ASC')
            ->orderBy('kode_poli', 'ASC')
            ->orderBy('jam_mulai', 'ASC')
            ->get();

        return new JsonResponse([
            'status' => 'success',
            'periode' => [
                'mulai' => $tglMulai,
                'selesai' => $tglSelesai
            ],
            'total' => $data->count(),
            'data' => $data
        ]);
    }

    /**
     * Memicu sinkronisasi manual dari BPJS ke jadwal_poli_cache
     */
    public function sync(Request $request)
    {
        Artisan::call('antrean:sync-jadwal-poli');
        $output = Artisan::output();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Proses sinkronisasi jadwal poli berhasil dijalankan',
            'output' => trim($output)
        ]);
    }

    /**
     * Mengambil data Rilis Jadwal Poli dari JadwalPoliCache
     */
    public function rilis(Request $request)
    {
        $startOfWeek = Carbon::now('Asia/Jakarta')->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $endOfWeek = Carbon::now('Asia/Jakarta')->startOfWeek(Carbon::MONDAY)->addDays(6)->format('Y-m-d');

        $tglMulai = $request->query('tgl_mulai', $startOfWeek);
        $tglSelesai = $request->query('tgl_selesai', $endOfWeek);

        $query = JadwalPoliCache::with(['poli', 'pegawai']);

        // Jika ada filter tanggal spesifik
        if ($request->has('tanggal') && !empty($request->tanggal)) {
            $query->where('tanggal', $request->tanggal);
        } else {
            $query->whereBetween('tanggal', [$tglMulai, $tglSelesai]);
        }

        // Filter status (default AKTIF jika tidak ditentukan)
        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', strtoupper($request->status));
        }

        // Filter berdasarkan kode_poli
        if ($request->has('kode_poli') && !empty($request->kode_poli)) {
            $query->where('kode_poli', $request->kode_poli);
        }

        // Pencarian nama dokter / poli / kode poli
        if ($request->has('q') && !empty($request->q)) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('nama_dokter', 'LIKE', "%{$q}%")
                    ->orWhere('nama_poli', 'LIKE', "%{$q}%")
                    ->orWhere('kode_poli', 'LIKE', "%{$q}%");
            });
        }

        $data = $query->orderBy('tanggal', 'ASC')
            ->orderBy('kode_poli', 'ASC')
            ->orderBy('jam_mulai', 'ASC')
            ->get();

        return new JsonResponse([
            'status' => 'success',
            'total' => $data->count(),
            'data' => $data
        ], 200);
    }
}
