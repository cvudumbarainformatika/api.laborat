<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Laborat;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Laborat\LaboratMeta;
use App\Models\Simrs\Penunjang\Laborat\MasterLaborat;
use Illuminate\Http\Request;

class LaboratController extends Controller
{
    public function listmasterpemeriksaanpoli()
    {
        $cito = request('cito');
        if ($cito == 1) {
            $listmasterpemeriksaanpoli = MasterLaborat::select(
                'rs1 as kode',
                'rs2 as pemeriksaan',
                'rs3 as hargasaranapoliumum',
                'rs4 as hargapelayananpoliumum',
                'rs5 as hargasaranapolispesialis',
                'rs6 as hargapelayananpolispesialis',
                'rs21 as gruper',
                'nilainormal',
                'satuan'
            )->where('rs25', '1')
                ->where('rs1', '!=', 'LAB126')
                ->where('hidden', '!=', '1')
                ->orderBy('rs2')->get();

            return $listmasterpemeriksaanpoli;
        } else {

            $listmasterpemeriksaanpoli = MasterLaborat::select(
                'rs1 as kode',
                'rs2 as pemeriksaan',
                'rs3 as hargasaranapoliumum',
                'rs4 as hargapelayananpoliumum',
                'rs5 as hargasaranapolispesialis',
                'rs6 as hargapelayananpolispesialis',
                'rs21 as gruper',
                'nilainormal',
                'satuan'
            )->where('rs25', '1')->orwhere('rs25', '')
                ->where('rs1', '!=', 'LAB126')
                ->where('hidden', '!=', '1')
                ->orderBy('rs2')->get();

            return $listmasterpemeriksaanpoli;
        }
    }

    public function simpanpermintaanlaborat(Request $request)
    {
        if ($request->nota == '' || $request->nota == null) {
            $simpanpermintaanlaborat = LaboratMeta::create(
                [
                    'noreg' => $request->noreg,
                    'norm' => $request->norm,
                    'nota' => $request->nota,
                    'jenis_laborat' => $request->jenis_laborat,
                    'tgl_order' => $request->tgl_order,
                    'puasa_pasien' => $request->puasa_pasien,
                    'tgl_permintaan' => $request->tgl_permintaan,
                    'dokter_pengirim' => auth()->user()->pegawai_id,
                    'faskes_pengirim' => $request->faskes_pengirim,
                    'unit_pengirim' => $request->unit_pengirim,
                    'prioritas_pemeriksaan' => $request->prioritas_pemeriksaan,
                    'diagnosa_masalah' => $request->diagnosa_masalah,
                    'catatan_permintaan' => $request->catatan_permintaan,
                    'metode_pengiriman_hasil' => $request->metode_pengiriman_hasil,
                    'asal_sumber_spesimen' => $request->asal_sumber_spesimen,
                    'jumlah_spesimen' => $request->jumlah_spesimen,
                    'volume_spesimen_klinis' => $request->volume_spesimen_klinis,
                    'cara_pengambilan_spesimen' => $request->cara_pengambilan_spesimen,
                    'waktu_pengambilan_spesimen' => $request->waktu_pengambilan_spesimen,
                    'kondisi_spesimen_waktu_diambil' => $request->kondisi_spesimen_waktu_diambil,
                    'waktu_fiksasi_spesimen' => $request->waktu_fiksasi_spesimen,
                    'cairan_fiksasi' => $request->cairan_fiksasi,
                    'volume_cairan_fiksasi' => $request->volume_cairan_fiksasi,
                    'petugas_pengambil_spesimen' => auth()->user()->pegawai_id,
                    'petugas_penerima_spesimen' => auth()->user()->pegawai_id,
                    'petugas_penganalisa' => auth()->user()->pegawai_id,
                ]
            );
        }
    }
}
