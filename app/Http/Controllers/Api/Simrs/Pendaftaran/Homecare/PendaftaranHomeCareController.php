<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Homecare;

use App\Helpers\FormatingHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\DaftarrajalController;
use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Homecare\HomeCareAdmin;
use App\Models\Simrs\Homecare\HomeCareKunjungan;
use App\Models\Simrs\Master\Mpasien;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendaftaranHomeCareController extends Controller
{
    public function getDokter()
    {
        $data = Petugas::select(
            'nama',
            'kdpegsimrs as dpjp'
        )->where('kdgroupnakes', '1')->where('aktif', 'aktif')->get();
        return new JsonResponse($data);
    }
    public function layananAdminHomeCare()
    {
        $data = HomeCareAdmin::whereNull('flag')->get();
        return new JsonResponse($data);
    }
    public function listKunjungan()
    {
        $req = [
            'order_by' => request('order_by') ?? 'created_at',
            'q' => request('q') ?? null,
            'page' => request('page') ?? 1,
            'per_page' => request('per_page') ?? 10,
            'tgl' => request('tgl') ?? null,
            'from' => request('from') ?? null,
            'to' => request('to') ?? null,
            'flag' => request('flag') ?? null,

        ];

        $raw = HomeCareKunjungan::query()
            ->when($req['q'], function ($q) use ($req) {
                $q->select('home_care_kunjungans.*')
                    ->leftJoin('rs15', 'rs15.rs1', '=', 'home_care_kunjungans.norm')
                    ->where(function ($y) use ($req) {
                        $y->where('rs15.rs2', 'LIKE', '%' . $req['q'] . '%')
                            ->orWhere('rs15.rs1', 'LIKE', '%' . $req['q'] . '%')
                            ->orWhere('home_care_kunjungans.noreg', 'LIKE', '%' . $req['q'] . '%');
                    });
            })
            ->when($req['tgl'], function ($q) use ($req) {
                $q->whereDate('tgl_kunjungan', $req['tgl']);
            })
            ->when($req['flag'], function ($q) use ($req) {
                $flag = strtolower($req['flag']);
                if ($flag == 'terlayani') $q->where('flag', '1');
                else if ($flag == 'belum terlayani') $q->where(function ($y) {
                    $y->whereNull('flag')->orWhere('flag', '');
                });
            })
            ->when($req['from'], function ($q) use ($req) {
                $q->whereBetween('tgl_kunjungan', [$req['from'] . ' 00:00:00', $req['to'] . ' 23:59:59']);
            })
            ->with([
                'masterpasien:rs1,rs2,rs17,rs16 as tgllahir',
                'poli:rs1,rs2',
                'dokter:nama,kdpegsimrs',
            ]);
        $totalCount = (clone $raw)->count();
        $data = $raw->simplePaginate($req['per_page']);

        $resp = ResponseHelper::responseGetSimplePaginate($data, $req, $totalCount);
        return new JsonResponse($resp);

        // $data = request()->all();
        // $meta = request()->all();
        // return new JsonResponse([
        //     'meta' => $meta,
        //     'data' => $data
        // ]);
    }
    public function simpanKunjungan(Request $request)
    {
        // cek norm dan nik jika pasien baru
        if ($request->barulama === 'baru') {
            $data = Mpasien::where('rs1', $request->norm)->first();
            if ($data) {
                return new JsonResponse([
                    'message' => 'Nomor RM Sudah ada',
                    'data' => $data
                ], 410);
            }
            $data2 = Mpasien::where('rs49', $request->nik)->first();
            if ($data2) {
                return new JsonResponse([
                    'message' => 'NIK Sudah ada',
                    'data' => $data
                ], 410);
            }
        }
        $masterpasien = DaftarrajalController::simpanMpasien($request);
        if (!$masterpasien) {
            return new JsonResponse(['message' => 'DATA MASTER PASIEN GAGAL DISIMPAN/DIUPDATE'], 410);
        }
        $nomor = str_pad(date('dHis'), 10, '0', STR_PAD_LEFT);
        $noreg = $nomor . "/" . date("m") . "/" . date("Y") . "/H";
        // cek unique noreg
        $ada = HomeCareKunjungan::where('noreg', $noreg)->first();
        if ($ada) return new JsonResponse(['message' => 'Noreg Sudah Ada, silahkan coba simpan lagi.'], 410);
        $user = auth()->user();
        $simpan = HomeCareKunjungan::create([
            'noreg' => $noreg,
            'norm' => $request->norm,
            'kode_poli' => $request->kode_poli,
            'tgl_kunjungan' => $request->tglmasuk,
            'kode_admin_layanan' => $request->kode_layanan,
            'nama_admin_layanan' => $request->nama_layanan,
            'sistem_bayar' => $request->sistembayar,
            'administrasi' => $request->administrasi,
            'js' => $request->js,
            'jp' => $request->jp,
            'dpjp' => $request->dpjp,
            'id_pegawai' => $user->pegawai_id,
        ]);

        return new JsonResponse([
            'data' => $simpan,
            'message' => 'Pendaftaran Kunjungan Home Care sudah disimpan'
        ]);
    }
}
