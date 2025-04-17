<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PencarianObatController extends Controller
{
    public function pencarianObatResep()
    {
        try {
            $kdruang = request('kdruang');
            $q = request('q');
            $groupsistembayar = request('groups');
            $tiperesep = request('tiperesep');
            
            // Prepare sistembayar array once
            $sistembayar = ((int)$groupsistembayar === 1) ? ['SEMUA', 'BPJS'] : ['SEMUA', 'UMUM'];
            
            // Build efficient base query
            $query = Mobatnew::query()
                ->select([
                    'new_masterobat.kd_obat',
                    'new_masterobat.nama_obat as namaobat',
                    'new_masterobat.kandungan',
                    'new_masterobat.bentuk_sediaan',
                    'new_masterobat.satuan_k as satuankecil',
                    'new_masterobat.status_fornas as fornas',
                    'new_masterobat.status_forkid as forkit',
                    'new_masterobat.status_generik as generik',
                    'new_masterobat.status_kronis as kronis',
                    'new_masterobat.status_prb as prb',
                    'new_masterobat.kode108',
                    'new_masterobat.uraian108',
                    'new_masterobat.kode50',
                    'new_masterobat.uraian50',
                    'new_masterobat.kekuatan_dosis as kekuatandosis',
                    'new_masterobat.volumesediaan',
                    'new_masterobat.kelompok_psikotropika as psikotropika',
                    'new_masterobat.jenis_perbekalan',
                    DB::raw('COALESCE(SUM(stokreal.jumlah), 0) as total')
                ])
                ->whereIn('new_masterobat.sistembayar', $sistembayar)
                ->where(function($query) use ($q) {
                    $query->where('new_masterobat.nama_obat', 'LIKE', "%{$q}%")
                          ->orWhere('new_masterobat.kandungan', 'LIKE', "%{$q}%");
                });

            // Add type-specific filters
            if ($tiperesep === 'prb') {
                $query->where('new_masterobat.status_prb', '!=', '');
            } elseif ($tiperesep === 'iter') {
                $query->where('new_masterobat.status_kronis', '!=', '');
            }

            // Optimize joins
            $query->leftJoin('stokreal', function($join) use ($kdruang) {
                $join->on('new_masterobat.kd_obat', '=', 'stokreal.kdobat')
                     ->where('stokreal.kdruang', '=', $kdruang);
            });

            // Add efficient relationships
            $result = $query->with([
                'onepermintaandeporinci' => function($q) use ($kdruang) {
                    $q->select(
                        'permintaan_r.kdobat',
                        DB::raw('COALESCE(SUM(permintaan_r.jumlah_minta), 0) as jumlah_minta')
                    )
                    ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                    ->where('permintaan_h.tujuan', $kdruang)
                    ->whereIn('permintaan_h.flag', ['', '1', '2'])
                    ->groupBy('permintaan_r.kdobat');
                },
                'oneperracikan' => function($q) use ($kdruang) {
                    $q->select(
                        'resep_permintaan_keluar_racikan.kdobat',
                        DB::raw('COALESCE(SUM(CASE WHEN resep_keluar_racikan_r.jumlah is null THEN resep_permintaan_keluar_racikan.jumlah ELSE 0 END), 0) as jumlah')
                    )
                    ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
                    ->leftJoin('resep_keluar_racikan_r', function($join) {
                        $join->on('resep_keluar_racikan_r.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
                            ->on('resep_keluar_racikan_r.kdobat', '=', 'resep_permintaan_keluar_racikan.kdobat');
                    })
                    ->where('resep_keluar_h.depo', $kdruang)
                    ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                    ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                },
                'onepermintaan' => function($q) use ($kdruang) {
                    $q->select(
                        'resep_permintaan_keluar.kdobat',
                        DB::raw('COALESCE(SUM(CASE WHEN resep_keluar_r.jumlah is null THEN resep_permintaan_keluar.jumlah ELSE 0 END), 0) as jumlah')
                    )
                    ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_permintaan_keluar.noresep')
                    ->leftJoin('resep_keluar_r', function($join) {
                        $join->on('resep_keluar_r.noresep', '=', 'resep_permintaan_keluar.noresep')
                            ->on('resep_keluar_r.kdobat', '=', 'resep_permintaan_keluar.kdobat');
                    })
                    ->where('resep_keluar_h.depo', $kdruang)
                    ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                    ->groupBy('resep_permintaan_keluar.kdobat');
                }
            ])
            ->groupBy('new_masterobat.kd_obat')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

            // Efficient transformation with null checks
            $wew = $result->map(function($item) {
                $total = $item->total ?? 0;
                $jumlahtransx = $item->oneperracikan ? collect($item->oneperracikan)->sum('jumlah') : 0;
                $jumlahtrans = $item->onepermintaan ? collect($item->onepermintaan)->sum('jumlah') : 0;
                $permintaanobatrinci = $item->onepermintaandeporinci ? collect($item->onepermintaandeporinci)->sum('jumlah_minta') : 0;
                
                $alokasi = max(0, $total - $jumlahtransx - $jumlahtrans - $permintaanobatrinci);
                
                $item->alokasi = $alokasi;
                return $item;
            });

            return new JsonResponse(['dataobat' => $wew]);
            
        } catch (\Exception $e) {
            Log::error('Pencarian Obat Error: ' . $e->getMessage(), [
                'kdruang' => request('kdruang'),
                'query' => request('q'),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Terjadi kesalahan dalam pencarian obat',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}