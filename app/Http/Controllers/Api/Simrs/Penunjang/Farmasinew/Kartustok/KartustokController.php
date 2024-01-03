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
            'mkelasterapi',
            'saldoawal' => function ($saldo) use ($blnLaluAwal, $blnLaluAkhir) {
                $saldo->whereBetween('tglpenerimaan', [$blnLaluAwal, $blnLaluAkhir])
                    ->where('kdruang', request('koderuangan'));
            },
            'penerimaanrinci' => function ($q) use ($tglAwal, $tglAkhir) {
                $q->whereHas('header', function ($x) use ($tglAwal, $tglAkhir) {
                    $x->whereBetween('tglpenerimaan', [$tglAwal, $tglAkhir]);
                });
            },
            'mutasi' => function ($mts) use ($tglAwal, $tglAkhir) {
                $mts->whereBetween('created_at', [$tglAwal, $tglAkhir]);
            }
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
