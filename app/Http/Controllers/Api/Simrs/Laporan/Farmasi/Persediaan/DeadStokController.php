<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Persediaan;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeadStokController extends Controller
{
    public function deadStok()
    {
        $from = request('from');
        $to = request('to');
        $kodeRuang = request('tempat'); // Gd-05010100, etc. or 'all' for Rumah Sakit

        if (!$from || !$to || !$kodeRuang) {
            return new JsonResponse(['message' => 'Parameter tidak lengkap (tempat, from, dan to wajib diisi)'], 422);
        }

        // Setup Date Boundaries
        $start = Carbon::parse($from)->startOfDay()->format('Y-m-d H:i:s');
        $end = Carbon::parse($to)->endOfDay()->format('Y-m-d H:i:s');

        $dateFrom = Carbon::parse($from);
        $blnLalu = $dateFrom->copy()->subMonth()->format('Y-m');

        // Fetch master obat
        $masterObat = DB::connection('farmasi')->table('new_masterobat as m')
            ->where('m.flag', '!=', 1)
            ->get(['m.kd_obat', 'm.nama_obat', 'm.satuan_k', 'm.uraian50 as kode_belanja']);

        // 1. Saldo Awal (Stock Opname Bulan Sebelum 'from' month)
        $qAwalQuery = DB::connection('farmasi')->table('stokopname')
            ->select('kdobat', DB::raw("SUM(jumlah) as qty"), DB::raw("SUM(jumlah * harga) as val"))
            ->where('tglopname', 'LIKE', $blnLalu . '%')
            ->where('jumlah', '!=', 0);

        if ($kodeRuang !== 'all') {
            $qAwalQuery->where('kdruang', $kodeRuang);
        } else {
            $qAwalQuery->whereIn('kdruang', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
        }
        $awal = $qAwalQuery->groupBy('kdobat')->get()->keyBy('kdobat');

        // 2. Incoming Transactions (Sum in PHP to avoid slow DB Union)
        $masukList = [];

        // A. Mutasi Masuk
        $masukList[] = DB::connection('farmasi')->table('mutasi_gudangdepo as m')
            ->join('permintaan_h as h', 'h.no_permintaan', '=', 'm.no_permintaan')
            ->select('m.kd_obat as kdobat', DB::raw("SUM(m.jml) as qty"), DB::raw("SUM(m.jml * m.harga) as val"))
            ->whereBetween('h.tgl_terima_depo', [$start, $end])
            ->when($kodeRuang !== 'all', function ($q) use ($kodeRuang) {
                $q->where('h.dari', $kodeRuang);
            }, function ($q) {
                $q->whereIn('h.dari', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
            })
            ->groupBy('m.kd_obat')->get()->keyBy('kdobat');

        // B. Retur Penjualan
        $masukList[] = DB::connection('farmasi')->table('retur_penjualan_r as r')
            ->join('retur_penjualan_h as h', 'h.noretur', '=', 'r.noretur')
            ->join('resep_keluar_h as rh', 'rh.noresep', '=', 'r.noresep')
            ->select('r.kdobat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * r.harga_beli) as val"))
            ->whereBetween('h.tgl_retur', [$start, $end])
            ->when($kodeRuang !== 'all', function ($q) use ($kodeRuang) {
                $q->where('rh.depo', $kodeRuang);
            }, function ($q) {
                $q->whereIn('rh.depo', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
            })
            ->groupBy('r.kdobat')->get()->keyBy('kdobat');

        // C. Gudang specific
        $isGudang = $kodeRuang === 'all' || in_array($kodeRuang, ['Gd-05010100', 'Gd-03010100']);
        if ($isGudang) {
            $masukList[] = DB::connection('farmasi')->table('penerimaan_r as r')
                ->join('penerimaan_h as h', 'h.nopenerimaan', '=', 'r.nopenerimaan')
                ->select('r.kdobat', DB::raw("SUM(r.jml_terima_k) as qty"), DB::raw("SUM(r.jml_terima_k * r.harga_netto_kecil) as val"))
                ->where('h.kunci', '1')
                ->whereBetween('h.tglpenerimaan', [$start, $end])
                ->when($kodeRuang !== 'all', function ($q) use ($kodeRuang) {
                    $q->where('h.gudang', $kodeRuang);
                }, function ($q) {
                    $q->whereIn('h.gudang', ['Gd-05010100', 'Gd-03010100']);
                })
                ->groupBy('r.kdobat')->get()->keyBy('kdobat');

            $masukList[] = DB::connection('farmasi')->table('retur_gudang_details as r')
                ->join('retur_gudangs as h', 'h.no_retur', '=', 'r.no_retur')
                ->select('r.kd_obat as kdobat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * CAST(0 as DECIMAL)) as val"))
                ->where('h.kunci', '1')
                ->whereBetween('h.tgl_retur', [$start, $end])
                ->when($kodeRuang !== 'all', function ($q) use ($kodeRuang) {
                    $q->where('h.gudang', $kodeRuang);
                }, function ($q) {
                    $q->whereIn('h.gudang', ['Gd-05010100', 'Gd-03010100']);
                })
                ->groupBy('r.kd_obat')->get()->keyBy('kdobat');
        }

        // 3. Outgoing Transactions (Sum in PHP to avoid slow DB Union)
        $keluarList = [];

        // A. Mutasi Keluar
        $keluarList[] = DB::connection('farmasi')->table('mutasi_gudangdepo as m')
            ->join('permintaan_h as h', 'h.no_permintaan', '=', 'm.no_permintaan')
            ->select('m.kd_obat as kdobat', DB::raw("SUM(m.jml) as qty"), DB::raw("SUM(m.jml * m.harga) as val"))
            ->whereBetween('h.tgl_kirim_depo', [$start, $end])
            ->when($kodeRuang !== 'all', function ($q) use ($kodeRuang) {
                $q->where('h.tujuan', $kodeRuang);
            }, function ($q) {
                $q->whereIn('h.tujuan', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
            })
            ->groupBy('m.kd_obat')->get()->keyBy('kdobat');

        // B. Retur Ke Gudang
        $keluarList[] = DB::connection('farmasi')->table('retur_gudang_details as r')
            ->join('retur_gudangs as h', 'h.no_retur', '=', 'r.no_retur')
            ->select('r.kd_obat as kdobat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * CAST(0 as DECIMAL)) as val"))
            ->where('h.kunci', '1')
            ->whereBetween('h.tgl_retur', [$start, $end])
            ->when($kodeRuang !== 'all', function ($q) use ($kodeRuang) {
                $q->where('h.depo', $kodeRuang);
            }, function ($q) {
                $q->whereIn('h.depo', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
            })
            ->groupBy('r.kd_obat')->get()->keyBy('kdobat');

        // C. Resep Keluar (Biasa) - Optimasi FORCE INDEX tgl_selesai untuk menghindari full table scan
        $keluarList[] = DB::connection('farmasi')->table('resep_keluar_r as r')
            ->join(DB::raw('resep_keluar_h as h FORCE INDEX (tgl_selesai)'), 'h.noresep', '=', 'r.noresep')
            ->select('r.kdobat', DB::raw("SUM(r.jumlah) as qty"), DB::raw("SUM(r.jumlah * r.harga_beli) as val"))
            ->whereBetween('h.tgl_selesai', [$start, $end])
            ->whereIn('h.flag', ['3', '4'])
            ->where('r.jumlah', '>', 0)
            ->when($kodeRuang !== 'all', function ($q) use ($kodeRuang) {
                $q->where('h.depo', $kodeRuang);
            }, function ($q) {
                $q->whereIn('h.depo', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
            })
            ->groupBy('r.kdobat')->get()->keyBy('kdobat');

        // D. Resep Keluar (Racikan) - Optimasi FORCE INDEX tgl_selesai untuk menghindari full table scan
        $keluarList[] = DB::connection('farmasi')->table('resep_keluar_racikan_r as r')
            ->join(DB::raw('resep_keluar_h as h FORCE INDEX (tgl_selesai)'), 'h.noresep', '=', 'r.noresep')
            ->select('r.kdobat', DB::raw("SUM(r.jumlah) as qty"), DB::raw("SUM(r.jumlah * r.harga_beli) as val"))
            ->whereBetween('h.tgl_selesai', [$start, $end])
            ->whereIn('h.flag', ['3', '4'])
            ->where('r.jumlah', '>', 0)
            ->when($kodeRuang !== 'all', function ($q) use ($kodeRuang) {
                $q->where('h.depo', $kodeRuang);
            }, function ($q) {
                $q->whereIn('h.depo', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
            })
            ->groupBy('r.kdobat')->get()->keyBy('kdobat');

        // E. Barang Rusak
        $keluarList[] = DB::connection('farmasi')->table('barang_rusaks as r')
            ->select('r.kd_obat as kdobat', DB::raw("SUM(r.jumlah) as qty"), DB::raw("SUM(r.jumlah * CAST(0 as DECIMAL)) as val"))
            ->whereBetween('r.tgl_kunci', [$start, $end])
            ->where('r.kunci', '1')
            ->when($kodeRuang !== 'all', function ($q) use ($kodeRuang) {
                $q->where('r.gudang', $kodeRuang);
            }, function ($q) {
                $q->whereIn('r.gudang', ['Gd-05010100', 'Gd-03010100']);
            })
            ->groupBy('r.kd_obat')->get()->keyBy('kdobat');

        // F. OK Specific
        $isDepoOk = $kodeRuang === 'all' || $kodeRuang === 'Gd-04010103';
        if ($isDepoOk) {
            $keluarList[] = DB::connection('farmasi')->table('persiapan_operasi_distribusis as d')
                ->join('persiapan_operasis as h', 'h.nopermintaan', '=', 'd.nopermintaan')
                ->leftJoin(DB::raw('(SELECT kd_obat, nopenerimaan, MAX(harga) as harga FROM daftar_hargas GROUP BY kd_obat, nopenerimaan) as dh'), function ($join) {
                    $join->on('dh.nopenerimaan', '=', 'd.nopenerimaan')
                        ->on('dh.kd_obat', '=', 'd.kd_obat');
                })
                ->select('d.kd_obat as kdobat', DB::raw("SUM(d.jumlah) as qty"), DB::raw("SUM(d.jumlah * dh.harga) as val"))
                ->whereBetween('h.tgl_distribusi', [$start, $end])
                ->whereIn('h.flag', ['2', '3', '4'])
                ->groupBy('d.kd_obat')->get()->keyBy('kdobat');
        }

        // G. Gudang specific
        if ($isGudang) {
            $keluarList[] = DB::connection('farmasi')->table('retur_penyedia_r as r')
                ->join('retur_penyedia_h as h', 'h.no_retur', '=', 'r.no_retur')
                ->select('r.kd_obat as kdobat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * CAST(0 as DECIMAL)) as val"))
                ->whereBetween('h.tgl_kunci', [$start, $end])
                ->where('h.kunci', '1')
                ->when($kodeRuang !== 'all', function ($q) use ($kodeRuang) {
                    $q->where('h.gudang', $kodeRuang);
                }, function ($q) {
                    $q->whereIn('h.gudang', ['Gd-05010100', 'Gd-03010100']);
                })
                ->groupBy('r.kd_obat')->get()->keyBy('kdobat');

            $keluarList[] = DB::connection('farmasi')->table('pengembalian_rinci_fifos as r')
                ->join('pengembalians as h', 'h.nopengembalian', '=', 'r.nopengembalian')
                ->select('r.kdobat', DB::raw("SUM(r.jml_dikembalikan) as qty"), DB::raw("SUM(r.jml_dikembalikan * CAST(0 as DECIMAL)) as val"))
                ->whereBetween('h.tgl_kunci', [$start, $end])
                ->where('h.flag', '1')
                ->when($kodeRuang !== 'all', function ($q) use ($kodeRuang) {
                    $q->where('h.kdruang', $kodeRuang);
                }, function ($q) {
                    $q->whereIn('h.kdruang', ['Gd-05010100', 'Gd-03010100']);
                })
                ->groupBy('r.kdobat')->get()->keyBy('kdobat');
        }

        // H. Pemakaian Ruangan (Floor Stok / Ruangan)
        $keluarList[] = DB::connection('farmasi')->table('pemakaian_r as r')
            ->join('pemakaian_h as h', 'h.nopemakaian', '=', 'r.nopemakaian')
            ->leftJoin('penerimaan_r as pr', function ($join) {
                $join->on('pr.nopenerimaan', '=', 'r.nopenerimaan')
                    ->on('pr.kdobat', '=', 'r.kd_obat');
            })
            ->select('r.kd_obat as kdobat', DB::raw("SUM(r.jumlah) as qty"), DB::raw("SUM(r.jumlah * COALESCE(pr.harga_netto_kecil, 0)) as val"))
            ->whereBetween('h.tgl', [$start, $end])
            ->where('h.flag', '1')
            ->when($kodeRuang !== 'all', function ($q) use ($kodeRuang) {
                $q->where('h.kdruang', $kodeRuang);
            }, function ($q) {
                $q->whereIn('h.kdruang', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
            })
            ->groupBy('r.kd_obat')->get()->keyBy('kdobat');

        // 4. Expiration Date Subquery (GROUP_CONCAT multiple expiration dates formatted in DD-MM-YYYY)
        $qExpQuery = DB::connection('farmasi')->table('stokreal')
            ->select('kdobat', DB::raw("GROUP_CONCAT(DISTINCT DATE_FORMAT(tglexp, '%d-%m-%Y') ORDER BY tglexp ASC SEPARATOR ', ') as tglexp"))
            ->where('jumlah', '>', 0);
        if ($kodeRuang !== 'all') {
            $qExpQuery->where('kdruang', $kodeRuang);
        } else {
            $qExpQuery->whereIn('kdruang', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
        }
        $exp = $qExpQuery->groupBy('kdobat')->get()->keyBy('kdobat');

        // Merge in PHP (Highly efficient hash map lookup instead of expensive SQL left joins)
        $filteredData = [];
        $totalTersedia = 0;
        $totalDeadStok = 0;

        foreach ($masterObat as $m) {
            $kdObat = $m->kd_obat;

            $awalQty = isset($awal[$kdObat]) ? floatval($awal[$kdObat]->qty) : 0.0;
            $awalVal = isset($awal[$kdObat]) ? floatval($awal[$kdObat]->val) : 0.0;

            // Sum masuk from all subqueries
            $masukQty = 0.0;
            $masukVal = 0.0;
            foreach ($masukList as $sublist) {
                if (isset($sublist[$kdObat])) {
                    $masukQty += floatval($sublist[$kdObat]->qty);
                    $masukVal += floatval($sublist[$kdObat]->val);
                }
            }

            // Sum keluar from all subqueries
            $keluarQty = 0.0;
            $keluarVal = 0.0;
            foreach ($keluarList as $sublist) {
                if (isset($sublist[$kdObat])) {
                    $keluarQty += floatval($sublist[$kdObat]->qty);
                    $keluarVal += floatval($sublist[$kdObat]->val);
                }
            }

            $saldoAkhirQty = $awalQty + $masukQty - $keluarQty;
            $saldoAkhirVal = $awalVal + $masukVal - $keluarVal;

            if ($saldoAkhirQty > 0) {
                $totalTersedia++;
                $totalTransaksi = $masukQty + $keluarQty;
                
                $isDead = ($totalTransaksi == 0);
                if ($isDead) {
                    $totalDeadStok++;
                }

                $filteredData[] = [
                    'kd_obat' => $kdObat,
                    'nama_obat' => $m->nama_obat,
                    'satuan_k' => $m->satuan_k,
                    'kode_belanja' => $m->kode_belanja,
                    'expired_date' => isset($exp[$kdObat]) ? $exp[$kdObat]->tglexp : null,
                    'awal_qty' => $awalQty,
                    'awal_val' => $awalVal,
                    'masuk_qty' => $masukQty,
                    'masuk_val' => $masukVal,
                    'keluar_qty' => $keluarQty,
                    'keluar_val' => $keluarVal,
                    'saldo_akhir_qty' => $saldoAkhirQty,
                    'saldo_akhir_val' => $saldoAkhirVal,
                    'total_transaksi' => $totalTransaksi
                ];
            }
        }

        $percentageDeadStok = $totalTersedia > 0 ? round(($totalDeadStok / $totalTersedia) * 100, 2) : 0;

        return new JsonResponse([
            'data' => $filteredData,
            'total_tersedia' => $totalTersedia,
            'total_dead_stok' => $totalDeadStok,
            'persentase_dead_stok' => $percentageDeadStok,
        ]);
    }

    private function queryMasuk($kode_ruang, $start, $end)
    {
        $isGudang = $kode_ruang === 'all' || in_array($kode_ruang, ['Gd-05010100', 'Gd-03010100']);

        $qPenerimaan = null;
        $qReturKeGudang = null;

        if ($isGudang) {
            // Gudang: Penerimaan (Surat Jalan/Faktur)
            $qPenerimaan = DB::connection('farmasi')->table('penerimaan_r as r')
                ->join('penerimaan_h as h', 'h.nopenerimaan', '=', 'r.nopenerimaan')
                ->select('r.kdobat', DB::raw("SUM(r.jml_terima_k) as qty"), DB::raw("SUM(r.jml_terima_k * r.harga_netto_kecil) as val"))
                ->where('h.kunci', '1')
                ->whereBetween('h.tglpenerimaan', [$start, $end])
                ->when($kode_ruang !== 'all', function ($q) use ($kode_ruang) {
                    $q->where('h.gudang', $kode_ruang);
                }, function ($q) {
                    $q->whereIn('h.gudang', ['Gd-05010100', 'Gd-03010100']);
                })
                ->groupBy('r.kdobat');

            // Untuk Gudang, Retur dari Unit Lain (Retur Gudang) adalah MASUK
            $qReturKeGudang = DB::connection('farmasi')->table('retur_gudang_details as r')
                ->join('retur_gudangs as h', 'h.no_retur', '=', 'r.no_retur')
                ->select('r.kd_obat as kdobat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * CAST(0 as DECIMAL)) as val"))
                ->where('h.kunci', '1')
                ->whereBetween('h.tgl_retur', [$start, $end])
                ->when($kode_ruang !== 'all', function ($q) use ($kode_ruang) {
                    $q->where('h.gudang', $kode_ruang);
                }, function ($q) {
                    $q->whereIn('h.gudang', ['Gd-05010100', 'Gd-03010100']);
                })
                ->groupBy('r.kd_obat');
        }

        // 1. Mutasi Masuk (Dari Unit Lain ke Sini)
        $qMutasiMasuk = DB::connection('farmasi')->table('mutasi_gudangdepo as m')
            ->join('permintaan_h as h', 'h.no_permintaan', '=', 'm.no_permintaan')
            ->select('m.kd_obat as kdobat', DB::raw("SUM(m.jml) as qty"), DB::raw("SUM(m.jml * m.harga) as val"))
            ->whereBetween('h.tgl_terima_depo', [$start, $end])
            ->when($kode_ruang !== 'all', function ($q) use ($kode_ruang) {
                $q->where('h.dari', $kode_ruang);
            }, function ($q) {
                $q->whereIn('h.dari', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
            })
            ->groupBy('m.kd_obat');

        // 2. Retur Penjualan (Pasien ke Unit)
        $qReturPasien = DB::connection('farmasi')->table('retur_penjualan_r as r')
            ->join('retur_penjualan_h as h', 'h.noretur', '=', 'r.noretur')
            ->join('resep_keluar_h as rh', 'rh.noresep', '=', 'r.noresep')
            ->select('r.kdobat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * r.harga_beli) as val"))
            ->whereBetween('h.tgl_retur', [$start, $end])
            ->when($kode_ruang !== 'all', function ($q) use ($kode_ruang) {
                $q->where('rh.depo', $kode_ruang);
            }, function ($q) {
                $q->whereIn('rh.depo', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
            })
            ->groupBy('r.kdobat');

        // Compile Masuk
        $qMasuk = $qMutasiMasuk->unionAll($qReturPasien);
        if ($isGudang) {
            if ($qPenerimaan) $qMasuk = $qMasuk->unionAll($qPenerimaan);
            if ($qReturKeGudang) $qMasuk = $qMasuk->unionAll($qReturKeGudang);
        }

        return DB::connection('farmasi')->query()->fromSub($qMasuk, 'union_masuk')
            ->select('kdobat', DB::raw("SUM(qty) as qty"), DB::raw("SUM(val) as val"))
            ->groupBy('kdobat');
    }

    private function queryKeluar($kode_ruang, $start, $end)
    {
        $isGudang = $kode_ruang === 'all' || in_array($kode_ruang, ['Gd-05010100', 'Gd-03010100']);
        $isDepoOk = $kode_ruang === 'all' || $kode_ruang === 'Gd-04010103';

        // 1. Mutasi Keluar (Transfer ke Unit Lain)
        $qMutasiKeluar = DB::connection('farmasi')->table('mutasi_gudangdepo as m')
            ->join('permintaan_h as h', 'h.no_permintaan', '=', 'm.no_permintaan')
            ->select('m.kd_obat as kdobat', DB::raw("SUM(m.jml) as qty"), DB::raw("SUM(m.jml * m.harga) as val"))
            ->whereBetween('h.tgl_kirim_depo', [$start, $end])
            ->when($kode_ruang !== 'all', function ($q) use ($kode_ruang) {
                $q->where('h.tujuan', $kode_ruang);
            }, function ($q) {
                $q->whereIn('h.tujuan', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
            })
            ->groupBy('m.kd_obat');

        // 2. Retur Ke Gudang (Dari Depo)
        $qReturDariDepo = DB::connection('farmasi')->table('retur_gudang_details as r')
            ->join('retur_gudangs as h', 'h.no_retur', '=', 'r.no_retur')
            ->select('r.kd_obat as kdobat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * CAST(0 as DECIMAL)) as val"))
            ->where('h.kunci', '1')
            ->whereBetween('h.tgl_retur', [$start, $end])
            ->when($kode_ruang !== 'all', function ($q) use ($kode_ruang) {
                $q->where('h.depo', $kode_ruang);
            }, function ($q) {
                $q->whereIn('h.depo', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
            })
            ->groupBy('r.kd_obat');

        // 3. Resep Keluar (Biasa)
        $qResep = DB::connection('farmasi')->table('resep_keluar_r as r')
            ->join('resep_keluar_h as h', 'h.noresep', '=', 'r.noresep')
            ->select('r.kdobat', DB::raw("SUM(r.jumlah) as qty"), DB::raw("SUM(r.jumlah * r.harga_beli) as val"))
            ->whereBetween('h.tgl_selesai', [$start, $end])
            ->whereIn('h.flag', ['3', '4'])
            ->where('r.jumlah', '>', 0)
            ->when($kode_ruang !== 'all', function ($q) use ($kode_ruang) {
                $q->where('h.depo', $kode_ruang);
            }, function ($q) {
                $q->whereIn('h.depo', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
            })
            ->groupBy('r.kdobat');

        // 4. Resep Keluar (Racikan)
        $qResepRacikan = DB::connection('farmasi')->table('resep_keluar_racikan_r as r')
            ->join('resep_keluar_h as h', 'h.noresep', '=', 'r.noresep')
            ->select('r.kdobat', DB::raw("SUM(r.jumlah) as qty"), DB::raw("SUM(r.jumlah * r.harga_beli) as val"))
            ->whereBetween('h.tgl_selesai', [$start, $end])
            ->whereIn('h.flag', ['3', '4'])
            ->where('r.jumlah', '>', 0)
            ->when($kode_ruang !== 'all', function ($q) use ($kode_ruang) {
                $q->where('h.depo', $kode_ruang);
            }, function ($q) {
                $q->whereIn('h.depo', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104']);
            })
            ->groupBy('r.kdobat');

        // 5. Barang Rusak
        $qBarangRusak = DB::connection('farmasi')->table('barang_rusaks as r')
            ->select('r.kd_obat as kdobat', DB::raw("SUM(r.jumlah) as qty"), DB::raw("SUM(r.jumlah * CAST(0 as DECIMAL)) as val"))
            ->whereBetween('r.tgl_kunci', [$start, $end])
            ->where('r.kunci', '1')
            ->when($kode_ruang !== 'all', function ($q) use ($kode_ruang) {
                $q->where('r.gudang', $kode_ruang);
            }, function ($q) {
                $q->whereIn('r.gudang', ['Gd-05010100', 'Gd-03010100']);
            })
            ->groupBy('r.kd_obat');

        // OK Specific: Distribusi Persiapan Operasi
        $qDistribusiOK = null;
        if ($isDepoOk) {
            $qDistribusiOK = DB::connection('farmasi')->table('persiapan_operasi_distribusis as d')
                ->join('persiapan_operasis as h', 'h.nopermintaan', '=', 'd.nopermintaan')
                ->leftJoin(DB::raw('(SELECT kd_obat, nopenerimaan, MAX(harga) as harga FROM daftar_hargas GROUP BY kd_obat, nopenerimaan) as dh'), function ($join) {
                    $join->on('dh.nopenerimaan', '=', 'd.nopenerimaan')
                        ->on('dh.kd_obat', '=', 'd.kd_obat');
                })
                ->select('d.kd_obat as kdobat', DB::raw("SUM(d.jumlah) as qty"), DB::raw("SUM(d.jumlah * dh.harga) as val"))
                ->whereBetween('h.tgl_distribusi', [$start, $end])
                ->whereIn('h.flag', ['2', '3', '4'])
                ->groupBy('d.kd_obat');
        }

        // GABUNG KELUAR
        $qKeluar = $qMutasiKeluar
            ->unionAll($qResep)
            ->unionAll($qResepRacikan)
            ->unionAll($qReturDariDepo)
            ->unionAll($qBarangRusak);

        if ($qDistribusiOK) {
            $qKeluar = $qKeluar->unionAll($qDistribusiOK);
        }

        if ($isGudang) {
            // Retur PBF
            $qReturPbf = DB::connection('farmasi')->table('retur_penyedia_r as r')
                ->join('retur_penyedia_h as h', 'h.no_retur', '=', 'r.no_retur')
                ->select('r.kd_obat as kdobat', DB::raw("SUM(r.jumlah_retur) as qty"), DB::raw("SUM(r.jumlah_retur * CAST(0 as DECIMAL)) as val"))
                ->whereBetween('h.tgl_kunci', [$start, $end])
                ->where('h.kunci', '1')
                ->when($kode_ruang !== 'all', function ($q) use ($kode_ruang) {
                    $q->where('h.gudang', $kode_ruang);
                }, function ($q) {
                    $q->whereIn('h.gudang', ['Gd-05010100', 'Gd-03010100']);
                })
                ->groupBy('r.kd_obat');
            $qKeluar = $qKeluar->unionAll($qReturPbf);

            // Pengembalian Pinjaman
            $qPengembalian = DB::connection('farmasi')->table('pengembalian_rinci_fifos as r')
                ->join('pengembalians as h', 'h.nopengembalian', '=', 'r.nopengembalian')
                ->select('r.kdobat', DB::raw("SUM(r.jml_dikembalikan) as qty"), DB::raw("SUM(r.jml_dikembalikan * CAST(0 as DECIMAL)) as val"))
                ->whereBetween('h.tgl_kunci', [$start, $end])
                ->where('h.flag', '1')
                ->when($kode_ruang !== 'all', function ($q) use ($kode_ruang) {
                    $q->where('h.kdruang', $kode_ruang);
                }, function ($q) {
                    $q->whereIn('h.kdruang', ['Gd-05010100', 'Gd-03010100']);
                })
                ->groupBy('r.kdobat');
            $qKeluar = $qKeluar->unionAll($qPengembalian);
        }

        return DB::connection('farmasi')->query()->fromSub($qKeluar, 'union_keluar')
            ->select('kdobat', DB::raw("SUM(qty) as qty"), DB::raw("SUM(val) as val"))
            ->groupBy('kdobat');
    }
}
