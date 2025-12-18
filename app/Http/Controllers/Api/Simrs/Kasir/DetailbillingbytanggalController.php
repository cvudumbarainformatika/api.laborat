<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Pembayaran;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajal;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajallalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanheder;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanhedlalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalretur;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasi;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Radiologi\Transradiologi;
use App\Models\Simrs\Psikologitrans\Psikologitrans;
use App\Models\Simrs\Tindakan\Tindakan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DetailbillingbytanggalController extends Controller
{
     public static function pelayananrm($dari, $sampai)
    {
        $pelayananrm = Pembayaran::select('rs7', 'rs11')
            ->where('rs3', 'RM#')
            ->whereBetween('rs4', [$dari, $sampai])
            ->get();
             $pelayananrm = $pelayananrm->sum('subtotal');
        return $pelayananrm;
    }

    public static function kartuidentitas($dari, $sampai)
    {
        $kartuidentitas = Pembayaran::select('rs7', 'rs11')
            ->where('rs3', 'K1#')
            ->whereBetween('rs4', [$dari, $sampai])
            ->get();
             $kartuidentitas = $kartuidentitas->sum('subtotal');
        return $kartuidentitas;
    }

    public static function poliklinik($dari, $sampai)
    {
        $poliklinik = Pembayaran::select('rs7', 'rs11')
            ->where('rs3', 'K2#')
            ->whereBetween('rs4', [$dari, $sampai])
            ->get();
            $poliklinik = $poliklinik->sum('subtotal');
        return $poliklinik;
    }

    public static function konsulantarpoli($dari, $sampai)
    {
        $konsulantarpoli = Pembayaran::select('rs7', 'rs11')
            ->where('rs3', 'K3#')
             ->whereBetween('rs4', [$dari, $sampai])
             ->get();
         $konsulantarpoli = $konsulantarpoli->sum('subtotal');
        return $konsulantarpoli;
    }

    public static function tindakan($dari, $sampai)
    {
        $tindakan = Tindakan::select('rs73.rs1 as noreg', 'rs30.rs2 as keterangan', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5')
            ->join('rs30', 'rs73.rs4', 'rs30.rs1')
            ->join('rs19', 'rs73.rs22', 'rs19.rs1')
            ->where('rs19.rs4', 'Poliklinik')
            ->whereBetween('rs73.rs3', [$dari, $sampai])->get();
        return $tindakan;
    }

    public static function laborat($dari, $sampai)
    {
        $laboratecer = Laboratpemeriksaan::select('rs49.rs21 as wew', DB::raw('sum((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx'))
            ->whereBetween('rs51.rs3', [$dari, $sampai])
            ->join('rs49', 'rs51.rs4', 'rs49.rs1')
            ->where('rs49.rs21', '');
        $laboratx = Laboratpemeriksaan::select('rs49.rs21 as wew', DB::raw('((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx'))
             ->whereBetween('rs51.rs3', [$dari, $sampai])
            ->join('rs49', 'rs51.rs4', 'rs49.rs1')
            ->where('rs49.rs21', '!=', '')
            ->groupBy('rs49.rs21')
            ->union($laboratecer)
            ->get();
        $laborattindakan = Tindakan::whereBetween('rs3', [$dari, $sampai])
            ->where('rs22', 'LAB')
            ->get();
        $laborat = $laboratx->sum('subtotalx') + $laborattindakan->sum('subtotal');
        // $laborat = $laboratx->makeHidden('subtotal')->toArray();
        return $laborat;
    }

    public static function radiologi($dari, $sampai)
    {
        $radiologix = Transradiologi::select(DB::raw('((rs6+rs8)*rs24) as subtotalx'))
            ->whereBetween('rs3', [$dari, $sampai])->get();
        $radiologi = $radiologix->sum('subtotalx');
        return $radiologi;
    }

    public static function onedaycare($dari, $sampai)
    {
        $operasi = Kamaroperasi::whereBetween('rs3', [$dari, $sampai])->get();
        $tindakan = Tindakan::whereBetween('rs3', [$dari, $sampai])
            ->where('rs22', 'OPERASI')
            ->get();
        $onedaycare = $operasi->sum('subtotal') + $tindakan->sum('subtotal');
        return $onedaycare;
    }

    public static function fisioterapi($dari, $sampai)
    {
        $fisioterapi = Tindakan::whereBetween('rs3', [$dari, $sampai])
            ->where('rs22', 'FISIO')
            ->get();
        $fisioterapi = $fisioterapi->sum('subtotal');
        return $fisioterapi;
    }

    public static function hd($dari, $sampai)
    {
        $hd = Tindakan::whereBetween('rs3', [$dari, $sampai])
            ->where('rs22', 'PEN005')
            ->get();
        $hd = $hd->sum('subtotal');
        return $hd;
    }

    public static function penunjanglain($dari, $sampai)
    {
        $caripenunjnag = Mpoli::where('penunjang_lain', '1')->get();
        $kdpenunjnag = $caripenunjnag[0]->rs1;
        $tindakan = Tindakan::whereBetween('rs3', [$dari, $sampai])
            ->whereIn('rs22', [$kdpenunjnag])
            ->get();
        $penunjanglain = $tindakan->sum('subtotal');
        return $penunjanglain;
    }

    public static function psikologi($dari, $sampai)
    {
        $psikologix = Psikologitrans::whereBetween('rs3', [$dari, $sampai])
            ->get();
        $psikologi = $psikologix->sum('subtotal');
        return $psikologi;
    }

    public static function cardio($dari, $sampai)
    {
        $cardio = Tindakan::whereBetween('rs3', [$dari, $sampai])
            ->where('rs22', 'POL026')
            ->get();
        $cardio = $cardio->sum('subtotal');
        return $cardio;
    }

    public static function eeg($dari, $sampai)
    {
        $eeg = Tindakan::whereBetween('rs3', [$dari, $sampai])
            ->where('rs22', 'POL024')
            ->get();
        $eeg = $eeg->sum('subtotal');
        return $eeg;
    }

    public static function endoscopy($dari, $sampai)
    {
        $endoscopy = Tindakan::whereBetween('rs3', [$dari, $sampai])
            ->where('rs22', 'POL031')
            ->get();
        $endoscopy = $endoscopy->sum('subtotal');
        return $endoscopy;
    }

    // public static function farmasi($dari, $sampai)
    // {
    //     $nonracikan = Apotekrajal::whereBetween('rs3', [$dari, $sampai])->get();
    //     $nonracikanlalu = Apotekrajallalu::whereBetween('rs3', [$dari, $sampai])->get();

    //     $racikan = Apotekrajalracikanheder::select(DB::raw('((rs92.rs7*rs92.rs5)+rs91.rs8) as subtotal'))
    //         ->join('rs92', 'rs91.rs1', 'rs92.rs1')
    //         ->whereBetween('rs3', [$dari, $sampai])
    //         ->get();
    //     $racikanlalu = Apotekrajalracikanhedlalu::select(DB::raw('((rs164.rs7*rs164.rs5)+rs163.rs8) as subtotal'))
    //         ->join('rs164', 'rs163.rs1', 'rs164.rs1')
    //         ->whereBetween('rs3', [$dari, $sampai])
    //         ->get();
    //     $retur = Apotekrajalretur::select(DB::raw('(rs88.rs3*rs88.rs4) as subtotal'))
    //         ->where('rs88.rs1', $noreg)
    //         ->get();

    //     $obat = $nonracikan->sum('subtotal') + $nonracikanlalu->sum('subtotal') + $racikan->sum('subtotal') + $racikanlalu->sum('subtotal') - $retur->sum('subtotal');
    //     return $obat;
    // }

    public static function farmasinew($dari, $sampai)
    {
        $nonracikan = Resepkeluarheder::select(
        DB::raw('round((resep_keluar_r.jumlah*resep_keluar_r.harga_jual+resep_keluar_r.nilai_r)) as subtotal'))
        ->join('resep_keluar_r', 'resep_keluar_r.noresep', 'resep_keluar_h.noresep')
        ->whereBetween('resep_keluar_h.tgl_selesai', [$dari, $sampai])
        ->get();

        $racikan = Resepkeluarheder::select(
        DB::raw('round((resep_keluar_racikan_r.jumlah*resep_keluar_racikan_r.harga_jual)) as subtotal'))
        ->join('resep_keluar_racikan_r', 'resep_keluar_racikan_r.noresep', 'resep_keluar_h.noresep')
        ->whereBetween('resep_keluar_h.tgl_selesai', [$dari, $sampai])
        ->get();

        $racikan_R = Resepkeluarheder::select(
            DB::raw('resep_keluar_racikan_r.nilai_r as subtotal'))
            ->join('resep_keluar_racikan_r', 'resep_keluar_racikan_r.noresep', 'resep_keluar_h.noresep')
            ->whereBetween('resep_keluar_h.tgl_selesai', [$dari, $sampai])
            ->groupBy('resep_keluar_h.noresep')
            ->get();

        $farmasi = $nonracikan->sum('subtotal')+$racikan->sum('subtotal')+$racikan_R->sum('subtotal');
        return $farmasi;
    }

}
