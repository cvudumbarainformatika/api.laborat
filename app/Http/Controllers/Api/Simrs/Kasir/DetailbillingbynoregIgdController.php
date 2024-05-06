<?php
namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Rstigalimax;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Penunjang\Bdrs\Bdrstrans;
use App\Models\Simrs\Penunjang\Endoscopy\Endoscopy;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasiigd;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Radiologi\Transradiologi;
use App\Models\Simrs\Tindakan\Tindakan;
use Illuminate\Support\Facades\DB;

class DetailbillingbynoregIgdController extends Controller
{
    public static function adminigd($noreg)
    {
        $query = Rstigalimax::where('rs3', 'A2#')->where('rs1', $noreg)->get();
        $laborat = $query->sum('subtotal');
        return $laborat;
    }

    public static function tindakan($noreg)
    {
        $tindakan = Tindakan::select('rs73.rs1 as noreg', 'rs30.rs2 as keterangan', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5')
            ->join('rs30', 'rs73.rs4', 'rs30.rs1')
            ->join('rs19', 'rs73.rs22', 'rs19.rs1')
            ->where('rs19.rs4', 'Poliklinik')
            ->where('rs73.rs22', 'POL014')
            ->where('rs73.rs1', $noreg)->get();
        return $tindakan;
    }

    public static function laborat($noreg)
    {
        $laboratecer = Laboratpemeriksaan::select('rs49.rs21 as wew', DB::raw('sum((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx'))
            ->where('rs51.rs1', $noreg)
            ->join('rs49', 'rs51.rs4', 'rs49.rs1')
            ->where('rs49.rs21', '')
            ->where('rs51.rs23','POL014')
            ->where('rs51.rs18','!=','')
            ->where('rs51.lunas','!=','1');
        $laboratx = Laboratpemeriksaan::select('rs49.rs21 as wew', DB::raw('((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx'))
            ->where('rs51.rs1', $noreg)
            ->join('rs49', 'rs51.rs4', 'rs49.rs1')
            ->where('rs49.rs21', '!=', '')
            ->where('rs51.rs23','POL014')
            ->where('rs51.rs18','!=','')
            ->where('rs51.lunas','!=','1')
            ->groupBy('rs49.rs21')
            ->union($laboratecer)
            ->get();
        $laborattindakan = Tindakan::where('rs1', $noreg)
            ->where('rs22', 'LAB')
            ->get();
        $laborat = $laboratx->sum('subtotalx') + $laborattindakan->sum('subtotal');
        // $laborat = $laboratx->makeHidden('subtotal')->toArray();
        return $laborat;
    }

    public static function radiologi($noreg)
    {
        $radiologix = Transradiologi::select(DB::raw('((rs6+rs8)*rs24) as subtotalx'))
            ->where('rs1', $noreg)->get();
        $radiologi = $radiologix->sum('subtotalx');
        return $radiologi;
    }

    public static function fisioterapi($noreg)
    {
        $fisioterapi = Tindakan::where('rs1', $noreg)
            ->where('rs22', 'FISIO')
            ->get();
        $fisioterapi = $fisioterapi->sum('subtotal');
        return $fisioterapi;
    }

    public static function hd($noreg)
    {
        $hd = Tindakan::where('rs1', $noreg)
            ->where('rs22', 'PEN005')->where('rs25','POL014')
            ->get();
        $hd = $hd->sum('subtotal');
        return $hd;
    }

    public static function penunjanglain($noreg)
    {
        $caripenunjnag = Mpoli::where('penunjang_lain', '1')->get();
        $kdpenunjnag = $caripenunjnag[0]->rs1;
        $tindakan = Tindakan::where('rs1', $noreg)->where('rs25','POL014')
            ->whereIn('rs22', [$kdpenunjnag])
            ->get();
        $penunjanglain = $tindakan->sum('subtotal');
        return $penunjanglain;
    }

    public static function cardio($noreg)
    {
        $cardio = Tindakan::where('rs1', $noreg)
            ->where('rs22', 'POL026')
            ->get();
        $cardio = $cardio->sum('subtotal');
        return $cardio;
    }

    public static function eeg($noreg)
    {
        $eeg = Tindakan::where('rs1', $noreg)
            ->where('rs22', 'POL024')
            ->get();
        $eeg = $eeg->sum('subtotal');
        return $eeg;
    }

    public static function endoscopy($noreg)
    {
        $endoscopy = Tindakan::where('rs1', $noreg)
            ->where('rs22', 'POL031')
            ->get();
        $endoscopy = $endoscopy->sum('subtotal');
        return $endoscopy;
    }

    public static function bdrs($noreg)
    {
        $bdrs = Bdrstrans::where('rs1', $noreg)->where('rs14', 'POL014')->get();
        $bdrs = $bdrs->sum('subtotal');
        return $bdrs;
    }

    public static function okigd($noreg)
    {
        $okigd = Kamaroperasiigd::where('rs1', $noreg)->where('rs15', 'POL014')->get();
        $okigd = $okigd->sum('biaya');
        return $okigd;
    }

    public static function tindakanokigd($noreg)
    {
        $tindakanokigd = Tindakan::where('rs1', $noreg)->where('rs22','OPERASIIRD2')->get();
        $tindakanokigd = $tindakanokigd->sum('biaya');
        return $tindakanokigd;
    }
}
