<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Laborat;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Laborat\MasterLaborat;
use Illuminate\Http\Request;

class LaboratController extends Controller
{
    public function listmasterpemeriksaanpoli()
    {
        $cito = request('cito');
        $kodepoli = request('kodepoli');
        if ($cito == 1) {
            if ($kodepoli === 'POL022') {
                $listmasterpemeriksaanpoli = MasterLaborat::select('rs1', 'rs2', 'rs3', 'rs4', 'rs21', 'nilainormal', 'satuan')->where('rs25', '1')
                    ->where('rs1', '!=', 'LAB126')
                    ->where('hidden', '!=', '1')
                    ->groupBy('rs21')->orderBy('rs2')->get();

                return $listmasterpemeriksaanpoli;
            } else {
                $listmasterpemeriksaanpoli = MasterLaborat::select('rs1', 'rs2', 'rs5', 'rs6', 'rs21', 'nilainormal', 'satuan')->where('rs25', '1')
                    ->where('rs1', '!=', 'LAB126')
                    ->where('hidden', '!=', '1')
                    ->groupBy('rs21')->orderBy('rs2')->get();

                return $listmasterpemeriksaanpoli;
            }

            //return $listmasterpemeriksaanpoli[0]->rs21;
        } else {
            if ($kodepoli === 'POL022') {
                $listmasterpemeriksaanpoli = MasterLaborat::select('rs1', 'rs2', 'rs3', 'rs4', 'rs21', 'nilainormal', 'satuan')->where('rs25', '1')->orwhere('rs25', '')
                    ->where('rs1', '!=', 'LAB126')
                    ->where('hidden', '!=', '1')
                    ->groupBy('rs21')->orderBy('rs2')->get();
                return $listmasterpemeriksaanpoli[0]->rs21;
            } else {
                $listmasterpemeriksaanpoli = MasterLaborat::select('rs1', 'rs2', 'rs5', 'rs6', 'rs21', 'nilainormal', 'satuan')->where('rs25', '1')->orwhere('rs25', '')
                    ->where('rs1', '!=', 'LAB126')
                    ->where('hidden', '!=', '1')
                    ->groupBy('rs21')->orderBy('rs2')->get();
                return $listmasterpemeriksaanpoli[0]->rs21;
            }
        }
    }
}
