<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Laborat;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Laborat\LaboratMeta;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Laborat\MasterLaborat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        // if ($request->nota == '' || $request->nota == null) {
        DB::select('call nota_permintaanlab(@nomor)');
        $x = DB::table('rs1')->select('rs28')->get();
        $wew = $x[0]->rs28;
        $notapermintaanlab = FormatingHelper::formatallpermintaan($wew, 'J-LAB');

        $simpanpermintaanlaborat = LaboratMeta::firstOrNew(
            ['nota' => $request->nota ?? $notapermintaanlab, 'noreg' => $request->noreg, 'norm' => $request->norm],
            [


                'jenis_laborat' => $request->jenis_laborat,
                'tgl_order' => date('Y-m-d H:i:s'),
                'puasa_pasien' => $request->puasa_pasien,
                'tgl_permintaan' => date('Y-m-d H:i:s'),
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
                'waktu_pengambilan_spesimen' => date('Y-m-d H:i:s'),
                'kondisi_spesimen_waktu_diambil' => $request->kondisi_spesimen_waktu_diambil,
                'waktu_fiksasi_spesimen' => date('Y-m-d H:i:s'),
                'cairan_fiksasi' => $request->cairan_fiksasi,
                'volume_cairan_fiksasi' => $request->volume_cairan_fiksasi,
                'petugas_pengambil_spesimen' => auth()->user()->pegawai_id,
                'petugas_penerima_spesimen' => auth()->user()->pegawai_id,
                'petugas_penganalisa' => auth()->user()->pegawai_id,
            ]
        );

        $data = $request->details;
        foreach ($data as $key => $value) {
            $simpanpemeriksaan = Laboratpemeriksaan::firstOrNew(
                ['rs2' => $request->nota ?? $notapermintaanlab, 'rs1' => $request->noreg, 'rs4' => $value['kode']],
                [


                    'rs3' => date('Y-m-d H:i:s'),

                    'rs5' => $request->jumlah, //$value['jml_laborat'],
                    'rs6' => $request->biaya_sarana, //$value['hargasarana'],
                    'rs7' => $request->biaya_sarana, //$value['hargasarana'],
                    'rs8' => auth()->user()->pegawai_id,
                    'rs9' => auth()->user()->pegawai_id,
                    //'rs10' => $value['hargapelayanan'],
                    // 'rs11' => $value['penanda'],
                    'rs12' => $request->prioritas_pemeriksaan,
                    'rs13' => $request->biaya_layanan, //$value['hargapelayanan'],
                    'rs14' => $request->biaya_layanan, //$value['hargapelayanan'],
                    // 'rs15' => $value['warna'],
                    //'rs16' => $value['unit_pengirim'],
                    // 'rs17' => $value['kd_sistembayar'],
                    // 'rs18' => $value['y'],
                    //'rs19'  => auth()->user()->pegawai_id,
                    'rs23'  => $request->unit_pengirim, //$value['unit_pengirim'],
                    'rs24'  => $request->kdsistembayar //$value['kd_sistembayar']
                ]
            );
        };

        if (!$simpanpermintaanlaborat) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        $nota = LaboratMeta::select('nota')->where('noreg', $request->noreg)
            ->groupBy('nota')->orderBy('id', 'DESC')->get();

        return new JsonResponse(
            [
                'message' => 'Tindakan Berhasil Disimpan.',
                'result' => $simpanpermintaanlaborat->load(['details']),
                'nota' => $nota
            ],
            200
        );
        // return $simpanpermintaanlaborat;
        // }
    }
}
