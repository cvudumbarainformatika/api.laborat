<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Pembayaran;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Tindakan\Tindakan;
use App\Models\Simrs\Visite\Visite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DetailbillingbynoregController extends Controller
{
    public static function pelayananrm($noreg)
    {
        $pelayananrm = Pembayaran::select('rs1', 'rs7', 'rs11')
            ->where('rs3', 'RM#')
            ->where('rs1', $noreg)->get();
        return $pelayananrm;
    }

    public static function kartuidentitas($noreg)
    {
        $kartuidentitas = Pembayaran::select('rs1', 'rs7', 'rs11')
            ->where('rs3', 'K1#')
            ->where('rs1', $noreg)->get();
        return $kartuidentitas;
    }

    public static function poliklinik($noreg)
    {
        $poliklinik = Pembayaran::select('rs1', 'rs7', 'rs11')
            ->where('rs3', 'K2#')
            ->where('rs1', $noreg)->get();
        return $poliklinik;
    }

    public static function konsulantarpoli($noreg)
    {
        $konsulantarpoli = Pembayaran::select('rs1', 'rs7', 'rs11')
            ->where('rs3', 'K3#')
            ->where('rs1', $noreg)->get();
        return $konsulantarpoli;
    }

    public static function tindakan($noreg)
    {
        $tindakan = Tindakan::select('rs73.rs1 as noreg', 'rs30.rs2 as keterangan', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5')
            ->join('rs30', 'rs73.rs4', 'rs30.rs1')
            ->join('rs19', 'rs73.rs22', 'rs19.rs1')
            ->where('rs19.rs4', 'Poliklinik')
            ->where('rs73.rs1', $noreg)->get();
        return $tindakan;
    }

    public static function visite($noreg)
    {
        $visite = Visite::where('rs1', $noreg)->get();
        return $visite;
    }

    public static function laborat($noreg)
    {
        $laboratecer = Laboratpemeriksaan::select('rs49.rs21 as wew', DB::raw('sum((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx'))
            ->where('rs51.rs1', $noreg)
            ->join('rs49', 'rs51.rs4', 'rs49.rs1')
            ->where('rs49.rs21', '');
        $laboratx = Laboratpemeriksaan::select('rs49.rs21 as wew', DB::raw('((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx'))
            ->where('rs51.rs1', $noreg)
            ->join('rs49', 'rs51.rs4', 'rs49.rs1')
            ->where('rs49.rs21', '!=', '')
            ->groupBy('rs49.rs21')
            ->union($laboratecer)
            ->get();
        $laborat = $laboratx->sum('subtotalx');
        // $laborat = $laboratx->makeHidden('subtotal')->toArray();
        return $laborat;
    }
}
