<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Kartustok;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mapingkelasterapi;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KartustokController extends Controller
{

    public function index()
    {
        $koderuangan = request('koderuangan');
        $bulan = request('bulan');
        $tahun = request('tahun');
        $x = $tahun . '-' . $bulan;
        $tglAwal = $x . '-01';
        $tglAkhir = $x . '-31';
        $dateAwal = Carbon::parse($tglAwal);
        $dateAkhir = Carbon::parse($tglAkhir);
        $blnLaluAwal = $dateAwal->subMonth()->format('Y-m-d');
        $blnLaluAkhir = $dateAkhir->subMonth()->format('Y-m-d');
        // $date->format('Y-m-d')
        // return new JsonResponse($blnLaluAwal);

        $list = Mobatnew::with([
            // 'mkelasterapi', ini tidak ada hubungan nya dengan trasaksi..
            'saldoawal' => function ($saldo) use ($blnLaluAwal, $blnLaluAkhir) {
                $saldo->whereBetween('tglpenerimaan', [$blnLaluAwal, $blnLaluAkhir])
                    ->where('kdruang', request('koderuangan'));
            },
            // hanya ada jika koderuang itu adalah gudang
            'penerimaanrinci' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                $q->whereHas('header', function ($x) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $x->whereBetween('tglpenerimaan', [$tglAwal, $tglAkhir])
                        ->where('gudang', $koderuangan);
                });
            },
            // mutasi masuk baik dari gudang, ataupun depo termasuk didalamnya mutasi antar depo dan antar gudang
            'mutasimasuk' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                $q->whereHas('header', function ($x) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $x->whereBetween('tgl_terima_depo', [$tglAwal, $tglAkhir])
                        ->where('dari', $koderuangan);
                });
            },
            // mutasi keluar baik ke gudang(mutasi antar gudang), ataupun ke depo dan juga ke ruangan
            'mutasikeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                $q->whereHas('header', function ($x) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $x->whereBetween('tgl_kirim_depo', [$tglAwal, $tglAkhir])
                        ->where('tujuan', $koderuangan);
                });
            },
            // ini jika $koderuangan = Gd-04010103 (Depo OK)
            'persiapanoperasiretur' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                $q->whereHas('header', function ($x) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $x->whereBetween('tgl_retur', [$tglAwal, $tglAkhir]);
                    // ->where('tujuan', $koderuangan);
                });
            },
            // ini jika $koderuangan = Gd-04010103 (Depo OK)
            // ini keluarnya nanti jumlah_distribusi harus dikurangi jumlah_resep karena resep nanti akan di ambil juga
            'persiapanoperasikeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                $q->whereHas('header', function ($x) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $x->whereBetween('tgl_distribusi', [$tglAwal, $tglAkhir]);
                    // ->where('tujuan', $koderuangan);
                });
            },
            'resepkeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                $q->whereHas('header', function ($x) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $x->whereBetween('tgl_permintaan', [$tglAwal, $tglAkhir])
                        ->where('depo', $koderuangan);
                });
            },
            'resepkeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                $q->whereHas('header', function ($x) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $x->whereBetween('tgl_permintaan', [$tglAwal, $tglAkhir])
                        ->where('depo', $koderuangan);
                });
            },
            'resepkeluarracikan' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                $q->whereHas('header', function ($x) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $x->whereBetween('tgl_permintaan', [$tglAwal, $tglAkhir])
                        ->where('depo', $koderuangan);
                });
            },
            // 'mutasi' => function ($mts) use ($tglAwal, $tglAkhir) {
            //     $mts->whereBetween('created_at', [$tglAwal, $tglAkhir]);
            // }

        ])
            ->where(function ($q) {
                $q->where('nama_obat', 'Like', '%' . request('q') . '%')
                    ->orWhere('merk', 'Like', '%' . request('q') . '%')
                    ->orWhere('kandungan', 'Like', '%' . request('q') . '%');
            })->orderBy('id', 'asc')
            ->where('flag', '')
            ->paginate(request('per_page'));

        return new JsonResponse($list);
    }

    public function cariobat()
    {

        $query = Mobatnew::select(
            'kd_obat as kodeobat',
            'nama_obat as namaobat',
            'satuan_k',
            'satuan_b',
        )->where('flag', '')
            ->where(function ($list) {
                $list->where('nama_obat', 'Like', '%' . request('q') . '%');
            })->orderBy('nama_obat')
            ->get();
        return new JsonResponse($query);
    }
}
