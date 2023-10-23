<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\Listkonsulantarpoli;
use Illuminate\Http\Request;

class DaftarkonsulantarpoliController extends Controller
{
    public function listkonsulantarpoli()
    {
        $listkonsulantarpoli = Listkonsulantarpoli::select('listkonsulanpoli.noreg_lama as noreg_lama','listkonsulanpoli.norm as norm',
        'listkonsulanpoli.tgl_kunjungan as tgl_kunjungan','listkonsulanpoli.tgl_rencana_konsul as tgl_rencana_konsul',
        'listkonsulanpoli.kdpoli_asal as kdpoli_asal','listkonsulanpoli.kdpoli_tujuan as kdpoli_tujuan',
        'listkonsulanpoli.kddokteer_asal as kddokteer_asal','rs15.rs2 as namapasien')
        ->join('rs15','listkonsulanpoli.norm','rs15.rs1')
    }
}
