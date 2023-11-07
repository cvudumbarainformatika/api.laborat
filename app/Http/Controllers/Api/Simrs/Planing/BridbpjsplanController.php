<?php

namespace App\Http\Controllers\Api\Simrs\Planing;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\DateHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Planing\Transrujukan;
use Illuminate\Http\Request;

class BridbpjsplanController extends Controller
{
    public static function bridcretaerujukan($request)
    {
        // menghindari lengt user bpjs yang minta minimal 3 karakter
        $data = auth()->user()->pegawai_id;
        $len = strlen($data);
        $use = $len === 1 ? '000' . $data : ($len === 2 ? '00' . $data : ($len === 3 ? '0' . $data : $data));

        $data = [
            "request" => [
                "t_rujukan" => [
                    "noSep" => $request->nosep,
                    "tglRujukan" => $request->tglrujukan,
                    'tglRencanaKunjungan' => $request->tglrencanakunjungan,
                    "ppkDirujuk" => $request->ppkdirujuk,
                    "jnsPelayanan" => $request->jenispelayanan,
                    "catatan" => $request->catatan,
                    "diagRujukan" => $request->diagnosarujukan,
                    "tipeRujukan" => $request->tiperujukan,
                    "poliRujukan" => $request->polirujukan,
                    'user' => $use

                    // "noSep" => '1327R0010923V008341',
                    // "tglRujukan" => '2023-09-29',
                    // 'tglRencanaKunjungan' => '2023-10-10',
                    // "ppkDirujuk" => '1323R001',
                    // "jnsPelayanan" => '2',
                    // "catatan" => 'coba ws',
                    // "diagRujukan" => 'A15',
                    // "tipeRujukan" => '0',
                    // "poliRujukan" => 'BSY',
                    // 'user' => auth()->user()->pegawai_id
                ]
            ]
        ];
        $tgltobpjshttpres = DateHelper::getDateTime();

        $bridcretaerujukan = BridgingbpjsHelper::post_url(
            'vclaim',
            'Rujukan/2.0/insert',
            $data
        );

        Bpjs_http_respon::create(
            [
                'method' => 'POST',
                'noreg' => $request->noreg,
                'request' => $data,
                'respon' => $bridcretaerujukan,
                'url' => '/Rujukan/2.0/insert',
                'tgl' => $tgltobpjshttpres
            ]
        );

        $xxx = $bridcretaerujukan['metadata']['code'];

        if ($xxx === 200 || $xxx === '200') {
            $norujukan = $bridcretaerujukan['response']['rujukan']['noRujukan'];
            $simpanrujukan = Transrujukan::create(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => $norujukan,
                    'rs4' => $request->nosep,
                    'rs5' => $request->tglrujukan,
                    'rs6' => $request->ppkdirujuk,
                    'rs7' => $request->ppkdirujukx,
                    'rs8' => $request->jenispelayanan,
                    'rs9' => $request->catatan,
                    'rs10' => $request->diagnosarujukan,
                    'rs11' => $request->tiperujukan,
                    'rs12' => $request->kodepoli,
                    'rs13' => date('Y-m-d H:i:s'),
                    'rs14' => auth()->user()->pegawai_id,
                    'rs15' => $request->noka,
                    'rs16' => $request->nama,
                    'rs17' => $request->kelamin,
                    'tglRencanaKunjungan' => $request->tglrencanakunjungan,
                    'diagnosa' => $request->diagnosa,
                    'poli' => $request->namapolirujukan,
                    'tipefaskes' => $request->tipefaskes,
                    'polix' => $request->polirujukan
                ]
            );

            if (!$simpanrujukan) {
                return 500;
            }
            return 200;
        }

        return $bridcretaerujukan;
    }

    public function deleterujukan()
    {
        $data = [
            "request" => [
                "t_rujukan" => [
                    "noRujukan" => "0301R0011117B000015",
                    "user" => "Coba Ws"
                ]
            ]
        ];

        $deleterujukan = BridgingbpjsHelper::post_url(
            'vclaim',
            'Rujukan/2.0/delete',
            $data
        );
        return $deleterujukan;
    }

    public function faskes()
    {
        $namafaskes = request('namafaskes');
        $jnsfaskes = request('jnsfaskes');
        $faskes = BridgingbpjsHelper::get_url('vclaim', '/referensi/faskes/' . $namafaskes . '/' . $jnsfaskes);
        return $faskes;
    }

    public function polibpjs()
    {
        $namapoli = request('namapoli');
        $poli = BridgingbpjsHelper::get_url('vclaim', '/referensi/poli/' . $namapoli);
        return $poli;
    }

    public static function createspri($request)
    {
        // menghindari lengt user bpjs yang minta minimal 3 karakter
        $data = auth()->user()->pegawai_id;
        $len = strlen($data);
        $use = $len === 1 ? '000' . $data : ($len === 2 ? '00' . $data : ($len === 3 ? '0' . $data : $data));

        $tgltobpjshttpres = DateHelper::getDateTime();
        $data = [
            "request" =>
            [
                "noKartu" => $request->noka,
                "kodeDokter" => $request->kodedokterdpjp,
                "poliKontrol" => $request->kdunit,
                "tglRencanaKontrol" => $request->tglrencanakontrol,
                "user" => $use
            ]
        ];

        $createspri = BridgingbpjsHelper::post_url(
            'vclaim',
            'RencanaKontrol/InsertSPRI',
            $data
        );
        Bpjs_http_respon::create(
            [
                'method' => 'POST',
                'noreg' => $request->noreg ?? '',
                'request' => $data,
                'respon' => $createspri,
                'url' => '/RencanaKontrol/InsertSPRI',
                'tgl' => $tgltobpjshttpres
            ]
        );
        return $createspri;
    }

    public function bridbpjslistrujukan()
    {
        $listrujukan = BridgingbpjsHelper::get_url('vclaim', '/Rujukan/Keluar/List/tglMulai/2023-10-10/tglAkhir/2023-10-10');
        return $listrujukan;
    }

    public static function insertsuratcontrol($request)
    {
        // menghindari lengt user bpjs yang minta minimal 3 karakter
        $data = auth()->user()->pegawai_id;
        $len = strlen($data);
        $use = $len === 1 ? '000' . $data : ($len === 2 ? '00' . $data : ($len === 3 ? '0' . $data : $data));

        $tgltobpjshttpres = DateHelper::getDateTime();
        $data = [
            "request" =>
            [
                "noSEP" => $request->nosep,
                "kodeDokter" => $request->kodedokterdpjp,
                "poliKontrol" => $request->kodepolibpjs,
                "tglRencanaKontrol" => $request->tglrencanakunjungan,
                "user" => $use
            ]
        ];

        $insernokontrol = BridgingbpjsHelper::post_url(
            'vclaim',
            'RencanaKontrol/insert',
            $data
        );

        Bpjs_http_respon::create(
            [
                'method' => 'POST',
                'noreg' => $request->noreg ?? '',
                'request' => $data,
                'respon' => $insernokontrol,
                'url' => '/RencanaKontrol/insert',
                'tgl' => $tgltobpjshttpres
            ]
        );
        return $insernokontrol;
    }
    public static function hapussuratcontrol($request, $nosurat)
    {
        // menghindari lengt user bpjs yang minta minimal 3 karakter
        $data = auth()->user()->pegawai_id;
        $len = strlen($data);
        $use = $len === 1 ? '000' . $data : ($len === 2 ? '00' . $data : ($len === 3 ? '0' . $data : $data));

        $tgltobpjshttpres = DateHelper::getDateTime();
        $data = [
            "request" =>
            [
                "t_suratkontrol" => [
                    "noSuratKontrol" => $nosurat,
                    "user" => $use
                ]
            ]
        ];

        $insernokontrol = BridgingbpjsHelper::post_url(
            'vclaim',
            'RencanaKontrol/Delete',
            $data
        );

        Bpjs_http_respon::create(
            [
                'method' => 'POST',
                'noreg' => $request->noreg ?? '',
                'request' => $data,
                'respon' => $insernokontrol,
                'url' => '/RencanaKontrol/delete',
                'tgl' => $tgltobpjshttpres
            ]
        );
        return $insernokontrol;
    }
}
