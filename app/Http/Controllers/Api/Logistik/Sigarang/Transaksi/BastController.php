<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Sigarang\Transaksi\Pemesanan\Pemesanan;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BastController extends Controller
{
    public function cariPerusahaan()
    {
        // ambil data kode perusahaan, masing2 satu aja
        $raw = Penerimaan::selectRaw('kode_perusahaan')->where('tanggal_bast', null)->distinct()->get();

        // map ke bentuk array
        $temp = collect($raw)->map(function ($y) {
            return $y->kode_perusahaan;
        });

        // ambil data perusahaan tsh, cuma butuh nama dan kode perusahaan saja. masing2 perusahaan cuma butuh satu.
        $data = KontrakPengerjaan::select('kodeperusahaan', 'namaperusahaan')->whereIn('kodeperusahaan', $temp)->distinct()->get();

        return new JsonResponse($data);
    }

    public function cariPemesanan()
    {
        $data = Penerimaan::select('nomor')->distinct()->where('tanggal_bast', null)
            ->where('kode_perusahaan', request('kode_perusahaan'))
            ->get();

        return new JsonResponse($data);
        // $anu['raw'] = $raw;
        // return new JsonResponse($anu);
    }

    public function ambilPemesanan()
    {
        $data = Pemesanan::where('nomor', request('nomor'))
            ->with('details', 'penerimaan.details')
            ->first();

        return new JsonResponse($data);
    }
}
