<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingbynoregController extends Controller
{
    public function billbynoregrajal()
    {
        $noreg = request('noreg');
        $query = KunjunganPoli::select(
            'rs17.rs1 as noreg',
        )
            ->where('rs17.rs1', $noreg)
            ->get();

        $pelayananrm = DetailbillingbynoregController::pelayananrm($noreg);
        $kartuidentitas = DetailbillingbynoregController::kartuidentitas($noreg);
        $poliklinik = DetailbillingbynoregController::poliklinik($noreg);
        $konsulantarpoli = DetailbillingbynoregController::konsulantarpoli($noreg);
        $tindakan = DetailbillingbynoregController::tindakan($noreg);
        $tindakanrinci = $tindakan->map(function ($tindakanx, $kunci) {
            return [
                'namatindakan' => $tindakanx->keterangan,
                'subtotal' => $tindakanx->subtotal,
            ];
        });
        //    $visite = DetailbillingbynoregController::visite($noreg);
        $laborat = DetailbillingbynoregController::laborat($noreg);
        $radiologi = DetailbillingbynoregController::radiologi($noreg);
        $onedaycare = DetailbillingbynoregController::onedaycare($noreg);
        $fisioterapi = DetailbillingbynoregController::fisioterapi($noreg);
        $hd = DetailbillingbynoregController::hd($noreg);
        $penunjanglain = DetailbillingbynoregController::penunjanglain($noreg);
        // $penunjanglainrinci = $penunjanglain->map(function ($penunjanglainx, $kunci) {
        //     return [
        //         'namatindakan' => $penunjanglainx->keterangan,
        //         'subtotal' => $penunjanglainx->subtotal,
        //     ];
        // });
        $psikologi = DetailbillingbynoregController::psikologi($noreg);
        $cardio = DetailbillingbynoregController::cardio($noreg);
        $eeg = DetailbillingbynoregController::eeg($noreg);
        $endoscopy = DetailbillingbynoregController::endoscopy($noreg);
        $obat = DetailbillingbynoregController::farmasi($noreg);

        $pelayananrm = (int) isset($pelayananrm[0]->subtotal) ? $pelayananrm[0]->subtotal : 0;
        $kartuidentitas = (int) isset($kartuidentitas[0]->subtotal) ? $kartuidentitas[0]->subtotal : 0;
        $poliklinik = (int) isset($poliklinik[0]->subtotal) ? $poliklinik[0]->subtotal : 0;
        $tindakanx = (int) $tindakan->sum('subtotal');

        $totalall =  $pelayananrm + $kartuidentitas + $poliklinik + $tindakanx + $laborat + $radiologi + $onedaycare
            + $fisioterapi + $hd + $penunjanglain
            + $psikologi + $cardio + $eeg + $endoscopy + $obat;
        return new JsonResponse(
            [
                'heder' => $query,
                'pelayananrm' => $pelayananrm,
                'kartuidentitas' => $kartuidentitas,
                'poliklinik' => $poliklinik,
                'konsulantarpoli' => isset($konsulantarpoli[0]->subtotal) ? $konsulantarpoli[0]->subtotal : 0,
                'tindakan' => isset($tindakanrinci) ?  $tindakanrinci : '',
                //        'visite' => isset($visite) ?  $visite : 0,
                'laborat' => isset($laborat) ?  $laborat : 0,
                'radiologi' => isset($radiologi) ?  $radiologi : 0,
                'onedaycare' => isset($onedaycare) ?  $onedaycare : 0,
                'fisioterapi' => isset($fisioterapi) ?  $fisioterapi : 0,
                'hd' => isset($hd) ?  $hd : 0,
                // 'penunjanglain' => isset($penunjanglain) ?  $penunjanglain : 0,
                'penunjanglain' => $penunjanglain,
                'psikologi' => isset($psikologi) ?  $psikologi : 0,
                'cardio' => isset($cardio) ?  $cardio : 0,
                'eeg' => isset($eeg) ?  $eeg : 0,
                'endoscopy' => isset($endoscopy) ?  $endoscopy : 0,
                'obat' => isset($obat) ?  $obat : 0,
                'totalall' => isset($totalall) ?  $totalall : 0,
            ]
        );
    }
}
