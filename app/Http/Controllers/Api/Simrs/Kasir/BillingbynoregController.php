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
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs8 as kodepoli',
            'rs19.rs2 as poli',
            'rs17.rs9 as kodedokter',
            'rs21.rs2 as dokter',
            'rs17.rs14 as kodesistembayar',
            'rs9.rs2 as sistembayar',
            DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
            DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                        TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                        TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            'rs15.rs16 as tgllahir',
            'rs15.rs17 as kelamin',
            'rs15.rs19 as pendidikan',
            'rs15.rs22 as agama',
            'rs15.rs37 as templahir',
            'rs15.rs39 as suku',
            'rs15.rs40 as jenispasien',
            'rs15.rs46 as noka',
            'rs15.rs49 as nktp',
            'rs15.rs55 as nohp',
            'rs222.rs8 as sep',
            'rs17.rs19 as status'
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
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
                'penunjanglain' => isset($penunjanglain) ?  $penunjanglain : 0,
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
