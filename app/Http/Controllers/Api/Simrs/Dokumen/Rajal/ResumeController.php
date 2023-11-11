<?php

namespace App\Http\Controllers\Api\Simrs\Dokumen\Rajal;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResumeController extends Controller
{
    public function resume()
    {
        $resume = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs9'
        )->with(
            [
                'dokter:rs1,rs2 as dokter',
                'diagnosa:rs1,rs3,rs4 as jenisdiagnosa,rs7 as kasus',
                'diagnosa.masterdiagnosa:rs1,rs4 as diagnosa',
                'anamnesis',
                'pemeriksaanfisik',
                'edukasi',
                'laborat:rs1,rs2,rs4,rs21,metode,tat',
                'laborat.pemeriksaanlab:rs1,rs2',
                'usg' => function ($usg) {
                    $usg->select('rs1', 'rs20 as hasil')->where('rs4', 'T00031')
                        ->orWhere('rs4', 'T00068')->orWhere('rs4', 'TX0128')
                        ->orWhere('rs4', 'TX0131');
                },
                'ecg' => function ($ecg) {
                    $ecg->select('rs1', 'rs20 as hasil')->where('rs4', 'POL009');
                },
                'eeg' => function ($eeg) {
                    $eeg->select('rs1', 'rs7 as tanggal', 'rs4 as klasifikasi', 'rs5 as impresi');
                },
                'apotekrajal' => function ($apotekrajal) {
                    $apotekrajal->select('rs90.rs1', 'rs32.rs2 as obat', 'rs90.rs8 as jumlah')
                        ->join('rs32', 'rs32.rs1', 'rs90.rs4');
                },
                'apotekrajalpolilalu' => function ($apotekrajalpolilalu) {
                    $apotekrajalpolilalu->select('rs162.rs1', 'rs32.rs2 as obat', 'rs162.rs8 as jumlah')
                        ->join('rs32', 'rs32.rs1', 'rs162.rs4');
                },
                'apotekracikanrajal' => function ($apotekracikanrajal) {
                    $apotekracikanrajal->select('rs32.rs2 as obat', 'rs92.rs5 as jumlah')
                        ->join('rs32', 'rs32.rs1', 'rs92.rs4');
                },
                'apotekracikanrajallalu' => function ($apotekracikanrajal) {
                    $apotekracikanrajal->select('rs32.rs2 as obat', 'rs164.rs5 as jumlah')
                        ->join('rs32', 'rs32.rs1', 'rs164.rs4');
                },
                'tindakan' => function ($tindakan) {
                    $tindakan->select('rs73.rs1', 'rs30.rs2 as tindakan')
                        ->join('rs30', 'rs30.rs1', 'rs73.rs4')
                        ->where('rs73.rs22', '!=', 'POL009');
                },
                'planning' => function ($planning) {
                    $planning
                        ->where('rs4', 'not like', '%Pulang%');
                },
            ]
        )->where('rs17.rs1', request('noreg'))
            ->get();
        return new JsonResponse($resume);
    }
}
