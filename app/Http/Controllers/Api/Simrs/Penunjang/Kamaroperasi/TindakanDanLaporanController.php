<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Laporan\Operasi\LaporanOperasi;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasi;
use App\Models\Simrs\Penunjang\Kamaroperasi\Masteroperasi;
use App\Models\Simrs\Tindakan\Tindakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TindakanDanLaporanController extends Controller
{
    public function getTindakanOp()
    {
        $data = Masteroperasi::select(
            'idx',
            'rs1 as kode',
            'rs2 as nama',
            'rs4 as jenis',
            'rs5 as smf',
            'rs6 as js3', // igd masuk tarif kelas 3
            'rs7 as jp3',
            'rs8 as an3',
            DB::raw('(rs6+rs7+rs8) as tarif3'),
            'rs9 as js1',
            'rs10 as jp1',
            'rs11 as an1',
            DB::raw('(rs9+rs10+rs11) as tarif1'),
            'rs12 as js_poli',
            'rs13 as jp_poli',
            DB::raw('(rs12+rs13) as tarif_poli'),
            'ssp',
            'psp',
            'asp',
            DB::raw('(ssp+psp+asp) as tarif_presiden'),
        )
            // ->when(request('q'), function ($q) {
            //     $q->where('rs2', 'like', '%' . request('q') . '%');
            // })
            ->get();

        return new JsonResponse([
            'data' => $data
        ]);
    }

    public function simpanTindakanOp(Request $request)
    {
        $nota = null;
        if ($request->nota == 'Baru') {
            DB::select('call nota_tindakan(@nomor)');
            $x = DB::table('rs1')->select('rs14')->first();
            $wew = $x->rs14;
            $nota = FormatingHelper::notatindakan($wew, 'TO');
        } else $nota = $request->nota;
        // return new JsonResponse([
        //     'req' => $request->all(),
        //     'nota' => $nota,
        //     'message' => 'Test '
        // ], 410);
        $cekLaporanOperasi = LaporanOperasi::where('rs1', $request->noreg)->where('rs2', $request->nota)->first();
        if ($cekLaporanOperasi)  return new JsonResponse(['message' => 'Laporan Operasi sudah dibuatkan, tidak boleh update tindakan operasi'], 410);
        $request->validate(
            [
                'noreg' => 'required|string',
                // 'nota' => 'required|string',
                'kode' => 'required|string',
                'tanggal' => 'required|date',
                'subtotal' => 'required|numeric|gt:0',
            ],
            [
                'noreg.required' => 'Nomor Registrasi Pasien kosong. silahkan pindah ke menu lain terlebih dahulu kemudian coba lagi',
                'nota.required' => 'Nomor Nota Pasien kosong. silahkan pindah ke menu lain terlebih dahulu kemudian coba lagi',
                'kode.required' => 'Tindakan kosong. silahkan Pilih tindakan',
                'tanggal.required' => 'Tanggal tidak boleh kosong. silahkan pilih tanggal',
                'subtotal.required' => 'Subtotal kosong',
                'subtotal.numeric' => 'Jasa sarana, pelayanan dan/atau anastesi harus angka',
                'subtotal.gt'      => 'Jasa sarana, pelayanan dan/atau anastesi harus lebih besar dari 0',
            ]
        );
        $flag = substr($request->noreg, -1);
        $cito = $request->cito ? 'cito' : '';
        $user = FormatingHelper::session_user();
        $tanggal = $request->tanggal ?? date('Y-m-d');
        try {
            DB::beginTransaction();
            $data = Kamaroperasi::updateOrCreate(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $nota,
                ],
                [
                    'rs3' => $tanggal . date(' H:i:s'),
                    'rs4' => $request->kode,
                    'rs5' => $request->js,
                    'rs6' => $request->jp,
                    'rs7' => $request->an,
                    'rs8' => 1,
                    'rs9' => $request->rs9,
                    'rs10' => $user['kodesimrs'],
                    'rs11' => $request->rs11,
                    'rs12' => $request->rs12,
                    'rs13' => $request->rs13,
                    'rs14' => $request->rs14,
                    'rs15' => $request->rs15,
                    'rs16' => $request->rs16,

                    'rs17' => $flag,
                    'rs18' => $cito,
                    'rs19' => $request->sisbaysplit,
                    'rs20' => $request->total_split,
                ]
            );
            if (!$data) throw new \Exception('Data gagal disimpan');
            DB::commit();
            $data->load('mastertindakanoperasi', 'laporanoperasi');
            return new JsonResponse([
                'message' => 'Sudah Disimpan',
                'data' => $data,
                'nota' => $nota
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 410);
        }
    }
    public function hapusTindakanOp(Request $request)
    {
        // return new JsonResponse($request->all());
        $cekLaporanOperasi = LaporanOperasi::where('rs1', $request->noreg)->where('rs2', $request->nota)->first();
        if ($cekLaporanOperasi)  return new JsonResponse(['message' => 'Laporan Operasi sudah dibuatkan, tidak boleh hapus tindakan operasi'], 410);
        try {
            DB::beginTransaction();
            $data = Kamaroperasi::find($request->id);
            if (!$data) throw new \Exception('Data tidak ditmukan');
            $data->delete();
            DB::commit();
            return new JsonResponse([
                'message' => 'Sudah Dihapus',
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 410);
        }
    }
    public function simpanLaporan(Request $request)
    {
        // return new JsonResponse($request->all());

        $cekTindakanOperasi = Kamaroperasi::where('rs1', $request->noreg)->where('rs2', $request->nota)->first();
        if (!$cekTindakanOperasi) {
            $tindakanKhusus = [
                'kanulasi vena sentral',
                'aff (pelepasan) double lumen',
                'reposisi mandibula (dislokasi)',
                'pelepasan drain',
                'lepas wsd'
            ];

            $cekTindakanLain = Tindakan::join('rs30', 'rs73.rs4', '=', 'rs30.rs1')
                ->where('rs73.rs1', $request->noreg)
                ->where('rs73.rs2', $request->nota)
                ->where(function ($query) use ($tindakanKhusus) {
                    foreach ($tindakanKhusus as $t) {
                        $query->orWhere('rs30.rs2', 'like', '%' . $t . '%');
                    }
                })
                ->select('rs73.id')
                ->first();

            if (!$cekTindakanLain) {
                return new JsonResponse(['message' => 'Tindakan Operasi / Tindakan Khusus belum dibuatkan, tidak bisa membuat laporan operasi'], 410);
            }
        }
        try {
            DB::beginTransaction();

            $data = LaporanOperasi::updateOrCreate(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->nota,
                ],
                [
                    'rs3' => $request->tanggal,
                    'rs4' => $request->rs4,
                    'rs5' => $request->rs5,
                    'rs6' => $request->rs6,
                    'rs7' => $request->rs7 ?? '',
                    'rs8' => $request->rs8,
                    'rs9' => $request->rs9,
                    'rs10' => $request->rs10,
                    'rs11' => $request->rs11,
                    'rs12' => $request->rs12,
                    'rs13' => $request->rs13,
                    'rs14' => $request->rs14,
                    'rs15' => $request->rs15,
                    'asa' => $request->asa,
                    'jenis_darah_masuk' => $request->jenis_darah_masuk,
                    'jd_keluar' => $request->jd_keluar,
                    'jd_masuk' => $request->jd_masuk,
                    'tindakan' => $request->tindakan,
                    'ttime' => !!$request->tTime ? '1' : '',

                ]
            );
            if (!$data) throw new \Exception('Data gagal disimpan');
            DB::commit();
            // $data->load('mastertindakanoperasi', 'laporanoperasi');
            return new JsonResponse([
                'message' => 'Sudah Disimpan',
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 410);
        }
    }
    public function hapusLaporannOp(Request $request)
    {
        // return new JsonResponse($request->all());
        try {
            DB::beginTransaction();
            $data = LaporanOperasi::find($request->id);
            if (!$data) throw new \Exception('Data tidak ditmukan');
            $data->delete();
            DB::commit();
            return new JsonResponse([
                'message' => 'Sudah Disimpan',
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 410);
        }
    }

    public function notatindakanok()
    {
        $nota = Tindakan::select('rs2 as nota')->where('rs1', request('noreg'))
            ->where('rs22', 'OPERASI')
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }
    public static function dataTindakanByNoreg($noreg, $ruangan)
    {
        $data = Tindakan::select(
            'id',
            'rs1',
            'rs2',
            'rs4',
            'rs1 as noreg',
            'rs2 as nota',
            'rs3',

            'rs4',
            'rs5',
            'rs6',
            'rs7',
            'rs8',
            'rs9',
            'rs13',
            'rs14',
            'rs20',
            'rs22',
            'rs23',
            'rs24',
        )
            ->with(['mastertindakan:rs1,rs2', 'sambungan:rs73_id,ket'])
            ->where('rs1', $noreg)
            // ->where('rs22', '!=', 'POL014')
            ->where('rs22', '=', 'OPERASI')
            ->get();

        return $data;
    }


    public function getTindakanRanap()
    {

        $data = self::dataTindakanByNoreg(request('noreg'), request('kodepoli'));
        return new JsonResponse($data);
    }

    public function simpantindakanranap(Request $request)
    {

        $cekKasir = DB::table('rs23')->select('rs42')->where('rs1', $request->noreg)->where('rs41', '=', '1')->get();

        if (count($cekKasir) > 0) {
            return response()->json(['status' => 'failed', 'message' => 'Maaf, data pasien telah dikunci oleh kasir pada tanggal ' . $cekKasir[0]->rs42], 500);
        }

        DB::select('call nota_tindakan(@nomor)');
        $x = DB::table('rs1')->select('rs14')->first();
        $wew = $x->rs14;
        $notatindakan = FormatingHelper::notatindakan($wew, 'T-OK');


        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];

        $nota = $request->nota ?? $notatindakan;

        // $tindakan = Tindakan::where(['rs1' => $request->noreg, 'rs4' => $request->kdtindakan, 'rs2' => $nota])->first();
        // if (!$tindakan) {
        //     $tindakan = new Tindakan();
        //     $tindakan->rs5 = $request->jmltindakan ?? '';
        // } else {
        //     $tindakan->rs5 = (int)$tindakan->rs5 + (int)$request->jmltindakan;
        // }

        $tindakan = new Tindakan();
        $tindakan->rs5 = $request->jmltindakan ?? '';

        $tindakan->rs2 = $nota;
        $tindakan->rs1 = $request->noreg ?? '';
        $tindakan->rs3 = date('Y-m-d H:i:s');
        $tindakan->rs4 = $request->kdtindakan ?? '';
        $tindakan->rs6 = $request->hargasarana ?? '';
        $tindakan->rs7 = $request->hargasarana ?? '';
        $tindakan->rs8 = $request->pelaksanaSatu ?? '';
        $tindakan->rs9 = $request->kddpjp ?? '';
        $tindakan->rs13 = $request->hargapelayanan ?? '';
        $tindakan->rs14 = $request->hargapelayanan ?? '';
        $tindakan->rs20 = $request->keterangan ?? '';
        // $tindakan->rs22 = $request->kdgroup_ruangan  ?? '';
        $tindakan->rs22 = 'OPERASI'  ?? '';
        $tindakan->rs23 = $request->pelaksanaDua ?? '';
        $tindakan->rs24 = $request->kdsistembayar ?? '';
        // $tindakan->rs25 = $request->kdpoli  ?? '';
        $tindakan->rs25 =  '';
        $tindakan->save();

        if (!$tindakan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        $idTindakan = $tindakan->id;

        $tindakan->sambungan()->updateOrCreate(
            ['rs73_id' => $idTindakan],
            [
                'nota' => $tindakan->rs2,
                'noreg' => $request->noreg,
                'kd_tindakan' => $request->kdtindakan,
                'ket' => $request->keterangan,
                'rs73_id' => $idTindakan
            ],
            // ['ket' => $request->keterangan]
        );


        // $tindakan->save();

        $nota = Tindakan::select('rs2 as nota')->where('rs1', $request->noreg)
            ->where('rs22', 'OPERASI')
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();

        // EwseklaimController::ewseklaimrajal_newclaim($request->noreg);

        $tindakan->load('mastertindakan:rs1,rs2,rs4');
        return new JsonResponse(
            [
                'message' => 'Tindakan Berhasil Disimpan.',
                'result' => $tindakan,
                'nota' => $nota
            ],
            200
        );
    }
}
