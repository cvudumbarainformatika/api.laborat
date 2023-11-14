<?php

namespace App\Http\Controllers\Api\Simrs\Rajal;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Pendaftaran\Rajalumum\Seprajal;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PoliController extends Controller
{
    public function kunjunganpoli()
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);

        $ruangan = $user->kdruangansim ?? '';

        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('to') . ' 00:00:00';
            $tglx = request('from') . ' 23:59:59';
        }

        if ($ruangan === 'POL023') {
            $kodepoli = ['POL015', 'POL023', 'POL038', 'POL039', 'POL040'];

            $status = request('status') ?? '';
            $daftarkunjunganpasienbpjs = KunjunganPoli::select(
                'rs17.rs1',
                'rs17.rs9',
                'rs17.rs1 as noreg',
                'rs17.rs2 as norm',
                'rs17.rs3 as tgl_kunjungan',
                'rs17.rs8 as kodepoli',
                'rs19.rs2 as poli',
                'rs19.rs6 as kodepolibpjs',
                'rs19.panggil_antrian as panggil_antrian',
                'rs17.rs9 as kodedokter',
                'master_poli_bpjs.nama as polibpjs',
                'rs21.rs2 as dokter',
                'rs17.rs14 as kodesistembayar',
                'rs9.rs2 as sistembayar',
                'rs9.groups as groups',
                'rs15.rs2 as nama_panggil',
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
                'rs222.rs5 as norujukan',
                'rs222.kodedokterdpjp as kodedokterdpjp',
                'rs222.dokterdpjp as dokterdpjp',
                'rs222.kdunit as kdunit',
                'rs17.rs19 as status'
            )
                ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
                ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
                ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
                ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
                ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
                ->leftjoin('master_poli_bpjs', 'rs19.rs6', '=', 'master_poli_bpjs.kode')
                ->whereBetween('rs17.rs3', [$tgl, $tglx])
                // ->where('rs17.rs8', $user->kdruangansim ?? '')
                ->where('rs19.rs4', '=', 'Poliklinik')
                ->whereIn('rs17.rs8', $kodepoli)
                ->where('rs17.rs8', '!=', 'POL014')
                //    ->where('rs9.rs9', '=', 'BPJS')
                ->where(function ($sts) use ($status) {
                    if ($status !== 'all') {
                        if ($status === '') {
                            $sts->where('rs17.rs19', '!=', '1');
                        } else {
                            $sts->where('rs17.rs19', '=', $status);
                        }
                    }
                })
                ->where(function ($query) {
                    $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
                })
                // ->where('rs17.rs8', 'LIKE', '%' . request('kdpoli') . '%')

                ->with([
                    'anamnesis', 'datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp',
                    'gambars',
                    'fisio',
                    'laborats' => function ($t) {
                        $t->with('details.pemeriksaanlab')
                            ->orderBy('id', 'DESC');
                    },
                    'radiologi' => function ($t) {
                        $t->orderBy('id', 'DESC');
                    },
                    'penunjanglain' => function ($t) {
                        $t->with('masterpenunjang')->orderBy('id', 'DESC');
                    },
                    'tindakan' => function ($t) {
                        $t->with('mastertindakan:rs1,rs2')
                            ->orderBy('id', 'DESC');
                    },
                    'diagnosa' => function ($d) {
                        $d->with('masterdiagnosa');
                    },
                    'pemeriksaanfisik' => function ($a) {
                        $a->with(['anatomys', 'detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                            ->orderBy('id', 'DESC');
                    },
                    'ok' => function ($q) {
                        $q->orderBy('id', 'DESC');
                    },
                    'taskid' => function ($q) {
                        $q->orderBy('taskid', 'DESC');
                    },
                    'planning' => function ($p) {
                        $p->with(
                            'masterpoli',
                            'rekomdpjp',
                            'transrujukan',
                            'listkonsul',
                            'spri',
                            'ranap',
                            'kontrol'
                        )->orderBy('id', 'DESC');
                    },
                    'edukasi' => function ($x) {
                        $x->orderBy('id', 'DESC');
                    },
                    'antrian_ambil' => function ($o) {
                        $o->where('pelayanan_id', request('kdpoli'));
                    },
                    'diet' => function ($diet) {
                        $diet->orderBy('id', 'DESC');
                    }
                ])
                ->orderby('rs17.rs3', 'ASC')
                ->paginate(request('per_page'));
        } else {
            $status = request('status') ?? '';
            $daftarkunjunganpasienbpjs = KunjunganPoli::select(
                'rs17.rs1',
                'rs17.rs9',
                'rs17.rs1 as noreg',
                'rs17.rs2 as norm',
                'rs17.rs3 as tgl_kunjungan',
                'rs17.rs8 as kodepoli',
                'rs19.rs2 as poli',
                'rs19.rs6 as kodepolibpjs',
                'rs19.panggil_antrian as panggil_antrian',
                'rs17.rs9 as kodedokter',
                'master_poli_bpjs.nama as polibpjs',
                'rs21.rs2 as dokter',
                'rs17.rs14 as kodesistembayar',
                'rs9.rs2 as sistembayar',
                'rs9.groups as groups',
                'rs15.rs2 as nama_panggil',
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
                'rs222.rs5 as norujukan',
                'rs222.kodedokterdpjp as kodedokterdpjp',
                'rs222.dokterdpjp as dokterdpjp',
                'rs222.kdunit as kdunit',
                'rs17.rs19 as status'
            )
                ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
                ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
                ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
                ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
                ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
                ->leftjoin('master_poli_bpjs', 'rs19.rs6', '=', 'master_poli_bpjs.kode')
                ->whereBetween('rs17.rs3', [$tgl, $tglx])
                // ->where('rs17.rs8', $user->kdruangansim ?? '')
                ->where('rs19.rs4', '=', 'Poliklinik')
                ->where('rs17.rs8', 'LIKE', '%' . $ruangan)
                ->where('rs17.rs8', '!=', 'POL014')
                //    ->where('rs9.rs9', '=', 'BPJS')
                ->where(function ($sts) use ($status) {
                    if ($status !== 'all') {
                        if ($status === '') {
                            $sts->where('rs17.rs19', '!=', '1');
                        } else {
                            $sts->where('rs17.rs19', '=', $status);
                        }
                    }
                })
                ->where(function ($query) {
                    $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
                })
                // ->where('rs17.rs8', 'LIKE', '%' . request('kdpoli') . '%')

                ->with([
                    'anamnesis', 'datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp',
                    'gambars',
                    'fisio',
                    'laborats' => function ($t) {
                        $t->with('details.pemeriksaanlab')
                            ->orderBy('id', 'DESC');
                    },
                    'radiologi' => function ($t) {
                        $t->orderBy('id', 'DESC');
                    },
                    'penunjanglain' => function ($t) {
                        $t->with('masterpenunjang')->orderBy('id', 'DESC');
                    },
                    'tindakan' => function ($t) {
                        $t->with('mastertindakan:rs1,rs2')
                            ->orderBy('id', 'DESC');
                    },
                    'diagnosa' => function ($d) {
                        $d->with('masterdiagnosa');
                    },
                    'pemeriksaanfisik' => function ($a) {
                        $a->with(['anatomys', 'detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                            ->orderBy('id', 'DESC');
                    },
                    'ok' => function ($q) {
                        $q->orderBy('id', 'DESC');
                    },
                    'taskid' => function ($q) {
                        $q->orderBy('taskid', 'DESC');
                    },
                    'planning' => function ($p) {
                        $p->with(
                            'masterpoli',
                            'rekomdpjp',
                            'transrujukan',
                            'listkonsul',
                            'spri',
                            'ranap',
                            'kontrol'
                        )->orderBy('id', 'DESC');
                    },
                    'edukasi' => function ($x) {
                        $x->orderBy('id', 'DESC');
                    },
                    'antrian_ambil' => function ($o) {
                        $o->where('pelayanan_id', request('kdpoli'));
                    },
                    'diet' => function ($diet) {
                        $diet->orderBy('id', 'DESC');
                    }
                ])
                ->orderby('rs17.rs3', 'DESC')
                ->paginate(request('per_page'));
            // ->simplePaginate(request('per_page'));
            // ->get();
        }

        return new JsonResponse($daftarkunjunganpasienbpjs);
    }

    public function save_pemeriksaanfisik(Request $request)
    {
        return new JsonResponse($request->all());
    }

    public function flagfinish(Request $request)
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);
        if ($user->kdgroupnakes === 1) {
            $updatekunjungan = KunjunganPoli::where('rs1', $request->noreg)->first();
            $updatekunjungan->rs19 = '1';
            $updatekunjungan->rs24 = '1';
            $updatekunjungan->save();
            return new JsonResponse(['message' => 'ok'], 200);
        } else {
            return new JsonResponse(['message' => 'MAAF FITUR INI HANYA UNTUK DOKTER...!!!'], 500);
        }
    }

    public function terimapasien(Request $request)
    {
        $cekx = KunjunganPoli::where('rs1', $request->noreg)->first();
        $flag = $cekx->rs19;
        if ($flag === '') {
            $updatekunjungan = KunjunganPoli::where('rs1', $request->noreg)->first();
            $updatekunjungan->rs19 = '2';
            $updatekunjungan->save();
            return new JsonResponse(['message' => 'ok'], 200);
        }
        //  return new JsonResponse([''], 500);
    }

    public function listdokter()
    {
        $listdokter = Mpegawaisimpeg::select('kdpegsimrs', 'nama')
            ->where('aktif', 'AKTIF')->where('kdgroupnakes', '1')
            ->get();

        return new JsonResponse($listdokter);
    }

    public function gantidpjp(Request $request)
    {
        $carikunjungan = KunjunganPoli::where('rs1', $request->noreg)->first();
        $carikunjungan->rs9 = $request->kdpegsimrs;
        $carikunjungan->save();
        return new JsonResponse(
            [
                'message' => 'ok',
                'result' => $carikunjungan->load('datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp'),
            ],
            200
        );
    }

    public function icare()
    {
        $wew = FormatingHelper::session_user();
        $x = $wew['kdgroupnakes'];
        $kddpjp = $wew['kddpjp'];

        if ($x === '1') {
            if ($kddpjp === '') {
                return new JsonResponse(['message' => 'Maaf Akun Anda Belum Termaping dengan Aplikasi Hafis...!!! '], 500);
            }
            $noka = request('noka');
            $data = [
                "param" => $noka,
                "kodedokter" => (int) $kddpjp
            ];

            // $data = [
            //     "param" => '0001538822259',
            //     "kodedokter" => 256319
            // ];

            $icare = BridgingbpjsHelper::post_url(
                'icare',
                'api/rs/validate',
                $data
            );
            return $icare;
        } else {
            return new JsonResponse(['message' => 'Maaf Fitur ini Hanya Untuk Dokter...!!!'], 500);
        }
    }
}
