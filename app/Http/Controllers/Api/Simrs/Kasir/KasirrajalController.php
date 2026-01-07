<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Helpers\bridgingbankjatimHelper;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Kasir\Karcis;
use App\Models\Simrs\Kasir\Kwitansidetail;
use App\Models\Simrs\Kasir\Kwitansilog;
use App\Models\Simrs\Kasir\Pembayaran;
use App\Models\Simrs\Kasir\Rstigalimax;
use App\Models\Simrs\Kasir\Tagihannontunai;
use App\Models\Simrs\Pendaftaran\Karcispoli;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasi;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Radiologi\Transradiologi;
use App\Models\Simrs\Psikologitrans\Psikologitrans;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Tindakan\Tindakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class KasirrajalController extends Controller
{
    public function kunjunganpoli()
    {
        $tgl = request('tgl');
        $daftarkunjunganpasienbpjs = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs8 as kodepoli',
            'rs19.rs2 as poli',
            'rs17.rs9 as kodedokter',
            'kepegx.pegawai.nama as dokter',
            'rs17.rs14 as kodesistembayar',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groupssistembayar',
            'rs15.rs3 as sapaan',
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
            'rs17.rs19 as status'
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            ->whereDate('rs17.rs3', $tgl)
            //->where('rs19.rs4', '=', 'Poliklinik')
            ->where('rs17.rs8', '!=', 'POL014')
            ->where('rs9.rs9', '=', 'UMUM')
            ->where(function ($query) {
                $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
            })
            ->orderby('rs17.rs3', 'DESC')
            ->paginate(request('per_page'));

        return new JsonResponse($daftarkunjunganpasienbpjs);
    }

    public function tagihanpergolongan()
    {
        $layanan = ['RM#', 'K1#', 'K2#', 'K3#', 'K4#', 'K5#', 'K6#'];
        $noreg = request('noreg');
        $nota = request('nota');
        if (request('golongan') == 'karcis') {
            $karcis = Pembayaran::where('rs1', $noreg)->whereIn('rs3', $layanan)->get();
            $tagihanpergolongan = $karcis->map(function ($karcisx, $kunci) {
                return [
                    'namatindakan' => $karcisx->rs6,
                    'subtotal' => $karcisx->rs7 + $karcisx->rs11,
                ];
            });
            $karcis = $karcis->sum('subtotal');
            $pembayaran = Karcis::where('noreg', $noreg)->first();
            $cek = Karcis::where('noreg', $noreg)->where('batal', '')->count();
            if ($cek > 0) {
                return new JsonResponse(
                    [
                        'flag' => 1,
                        'Terbayar' => 'Pelayanan Karcis',
                        'Subtotal' => $pembayaran->total
                    ]
                );
            } else {
                return new JsonResponse(
                    [
                        'flag' => 0,
                        'Pelayanan' => $tagihanpergolongan,
                        'Subtotal' => $karcis
                    ]
                );
            }
        } elseif (request('golongan') == 'konsulantarpoli') {
                $konsul = Pembayaran::where('rs1', $noreg)->where('rs3', 'K3#')->get();
                $konsulantarpoli = $konsul->map(function ($konsul, $kunci) {
                    return [
                        'namatindakan' => $konsul->rs6,
                        'subtotal' => $konsul->rs7 + $konsul->rs11,
                    ];
                });
                $konsul = $konsul->sum('subtotal');
                return new JsonResponse(
                    [
                        'Pelayanan' => $konsulantarpoli,
                        'Subtotal' => $konsul
                    ]
                );
            // } elseif (request('golongan') == 'tindakan psikologi') {
            //     $psikologitrans = Psikologitrans::select(
            //         'rs30.rs2 as tindakan',
            //         DB::raw('(psikologi_trans.rs7+psikologi_trans.rs13)*psikologi_trans.rs5 as subtotalx')
            //     )
            //         ->join('rs30', 'psikologi_trans.rs4', 'rs30.rs1')->where('psikologi_trans.rs1', $noreg)
            //         ->where('psikologi_trans.rs2', $nota)->where('psikologi_trans.rs22', '!=', 'OPERASI')
            //         ->get();
            //     $subtotal = $psikologitrans->map(function ($psikologitrans, $kunci) {
            //         return [
            //             'subtotal' => $psikologitrans->subtotalx,
            //         ];
            //     });
            //     $total = $subtotal->sum('subtotal');
            //     return new JsonResponse(
            //         [
            //             'Pelayanan' => $psikologitrans,
            //             'Subtotal' => $total
            //         ]
            //     );
        } elseif (request('golongan') == 'tindakan') {
            $psikologitrans = Psikologitrans::select(
                'rs30.rs2 as tindakan',
                DB::raw('(psikologi_trans.rs7+psikologi_trans.rs13)*psikologi_trans.rs5 as subtotalx')
            )
                ->join('rs30', 'psikologi_trans.rs4', 'rs30.rs1')->where('psikologi_trans.rs1', $noreg)
                ->where('psikologi_trans.rs2', $nota)->where('psikologi_trans.rs22', '!=', 'OPERASI');

            $tindakan = Tindakan::select(
                'rs30.rs2 as tindakan',
                DB::raw('(rs73.rs7+rs73.rs13)*rs73.rs5 as subtotalx')
            )
                ->join('rs30', 'rs73.rs4', 'rs30.rs1')->where('rs73.rs1', $noreg)
                ->where('rs73.rs2', $nota)->where('rs73.rs22', '!=', 'OPERASI')
                ->union($psikologitrans)
                ->get();
            $subtotal = $tindakan->map(function ($tindakan, $kunci) {
                return [
                    'subtotal' => $tindakan->subtotalx,
                ];
            });
            $total = $subtotal->sum('subtotal');

            return new JsonResponse(
                [
                    'Pelayanan' => $tindakan,
                    'Subtotal' => $total
                ]
            );
        } elseif (request('golongan') == 'laborat') {
            $laboratx = Laboratpemeriksaan::select(
                'rs49.rs2 as keterangan',
                DB::raw('(rs51.rs6+rs51.rs13) as subtotalx')
            )->join('rs49', 'rs49.rs1', 'rs51.rs4')
                ->where('rs51.rs1', $noreg)->where('rs51.rs2', $nota)
                ->where('rs49.rs21', '');

            $laborat = Laboratpemeriksaan::select(
                'rs49.rs21 as keterangan',
                DB::raw('(rs51.rs6+rs51.rs13) as subtotalx')
            )->join('rs49', 'rs49.rs1', 'rs51.rs4')
                ->where('rs51.rs1', $noreg)->where('rs51.rs2', $nota)
                ->where('rs49.rs21', '!=', '')
                ->groupBy('rs49.rs21')
                ->union($laboratx)
                ->get();
            $subtotal = $laborat->map(function ($laborat, $kunci) {
                return [
                    'subtotal' => $laborat->subtotalx,
                ];
            });
            $total = $subtotal->sum('subtotal');
            return new JsonResponse(
                [
                    'Pelayanan' => $laborat,
                    'Subtotal' => $total
                ]
            );
        } elseif (request('golongan') == 'radiologi') {
            $radiologi = Transradiologi::select(
                DB::raw('concat(rs47.rs2,rs47.rs3) as keterangan'),
                DB::raw('((rs48.rs6+rs48.rs8)*rs48.rs24) as subtotalx')
            )->join('rs47', 'rs47.rs1', 'rs48.rs4')
                ->where('rs48.rs1', $noreg)->where('rs48.rs2', $nota)
                ->get();
            $subtotal = $radiologi->map(function ($radiologi, $kunci) {
                return [
                    'subtotal' => $radiologi->subtotalx,
                ];
            });
            $total = $subtotal->sum('subtotal');
            return new JsonResponse(
                [
                    'Pelayanan' => $radiologi,
                    'Subtotal' => $total
                ]
            );
        } elseif (request('golongan') == 'operasibesar') {
            $operasibesar = Kamaroperasi::select(
                'rs53.rs2 as keterangan',
                DB::raw('((rs54.rs5+rs54.rs6+rs54.rs7)*rs54.rs8) as subtotalx')
            )->join('rs53', 'rs53.rs1', 'rs54.rs4')
                ->where('rs54.rs1', $noreg)->where('rs54.rs2', $nota)
                ->get();
            $subtotal = $operasibesar->map(function ($operasibesar, $kunci) {
                return [
                    'subtotal' => $operasibesar->subtotalx,
                ];
            });
            $total = $subtotal->sum('subtotal');
            return new JsonResponse(
                [
                    'Pelayanan' => $operasibesar,
                    'Subtotal' => $total
                ]
            );
        } elseif (request('golongan') == 'operasikecil') {
            $operasikecil = Tindakan::select(
                'rs30.rs2 as tindakan',
                DB::raw('(rs73.rs7+rs73.rs13)*rs73.rs5 as subtotalx')
            )
                ->join('rs30', 'rs73.rs4', 'rs30.rs1')->where('rs73.rs1', $noreg)
                ->where('rs73.rs2', $nota)->where('rs73.rs22', 'OPERASI')
                ->get();
            $subtotal = $operasikecil->map(function ($operasikecil, $kunci) {
                return [
                    'subtotal' => $operasikecil->subtotalx,
                ];
            });
            $total = $subtotal->sum('subtotal');
            return new JsonResponse(
                [
                    'Pelayanan' => $operasikecil,
                    'Subtotal' => $total
                ]
            );
        }
    }

    public static function getkarcisbynoreg($request)
    {
        $data = Karcis::with(
            [
                'rincian'
            ]
        )->where('batal', '!=', '1')
        ->where('noreg', $request->noreg)->get();
        return $data;
    }

    public function pembayarannonkarcis(Request $request)
    {
        $cek = Kwitansidetail::where('kwitansilog.noreg', $request->noreg)->where('kwitansilog.nota', $request->nota)->where('kwitansi_d.jenis', $request->jenis)
        ->leftJoin('kwitansilog', 'kwitansilog.nokwitansi', 'kwitansi_d.no_kwitansi')
        ->where('kwitansilog.batal', '!=', '1')->first();
        if($cek)
        {
            return new JsonResponse(['message' => 'No. Nota '.$request->nota.' Sudah Dibayar...!!! Dengan No. Kwitansi '.$cek->nokwitansi], 500);
        }

        $id_trans   = null;
        $unit       = null;
        $nokwitansi = null;
        try{
            DB::beginTransaction();
                if($request->carabayar === 'Tunai'){
                    DB::select('call nokwitansi(@nomor)');
                        $x = DB::table('rs1')->select('rs47')->get();
                        $wew = $x[0]->rs47;
                        $nokwitansi = FormatingHelper::nokwitansi($wew, 'RJ');

                        $user = Pegawai::find(auth()->user()->pegawai_id);
                        $kdpegsimrs = $user->kdpegsimrs;

                    $insertkwitansilog = Kwitansilog::firstOrCreate(
                        [
                            'nokwitansi' => $nokwitansi,
                        ],
                        [
                            'noreg' => $request->noreg,
                            'norm' => $request->norm,
                            'tgl' => date('Y-m-d H:i:s'),
                            'nama' => $request->nama,
                            'ruangan' => $request->poli ?? '-',
                            'sistembayar' => $request->sistembayar,
                            'total' => $request->total,
                            'flag' => 'Kasir Rajal',
                            'tglx' => date('Y-m-d H:i:s'),
                            'userid' => $kdpegsimrs,
                            'nota' => $request->nota,
                            'carabayar' => $request->carabayar,
                            'jenispembayaran' => $request->jenispembayaran
                        ]
                    );
                    if (!$insertkwitansilog) {
                        return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
                    }

                    $jenis = $request->jenis;
                    if($jenis === 'Tindakan')
                    {
                        $val = DB::table('rs73')
                            ->selectRaw("GROUP_CONCAT(rs73.id SEPARATOR ',') AS id_trans")
                            ->selectRaw("rs30.rs2 AS keterangan")
                            ->selectRaw("SUM((rs73.rs7+rs73.rs13)*rs73.rs5) AS subtotal")
                            ->selectRaw("rs73.rs22 AS kd_jenis")
                            ->selectRaw("rs19.rs2 AS jenis")
                            ->leftJoin('rs19', 'rs73.rs22', '=', 'rs19.rs1')
                            ->join('rs30', 'rs30.rs1', '=', 'rs73.rs4')
                            // ->join('rs21', 'rs21.rs1', '=', 'rs73.rs8')
                            ->where('rs73.rs1', $request->noreg)
                            ->where('rs73.rs2', $request->nota)
                        //    ->where('rs73.rs22', '<>', 'OPERASI')
                            ->groupBy('rs73.rs22')
                            ->get();

                        if($val->pluck('kd_jenis')->first() === 'FISIO'){
                            $unit = 'PEN004';
                        }else if($val->pluck('kd_jenis')->first() === 'OPERASI'){
                            $unit = 'PEN001';
                            $jenis = 'Tindakan Operasi';
                        }else{
                            $unit = $val->pluck('kd_jenis')->first();
                        }
                         $id_trans = $val->pluck('id_trans')->implode(',');
                    }else if($jenis === 'Laboratorium'){
                        $q1 = DB::table('rs49')
                            ->join('rs51', 'rs49.rs1', '=', 'rs51.rs4')
                            ->selectRaw("'' as flag, rs51.id as id_trans, rs51.rs2 as nota, rs51.rs3 as tgl, rs51.rs8 as kodedokter,
                                        rs49.rs2 as keterangan, (rs51.rs6+rs51.rs13) as biaya,
                                        rs51.rs5 as jml, ((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotal")
                            ->where('rs49.rs21', '=', '')
                            ->where('rs51.rs1', '=', $request->noreg)
                            ->where('rs51.rs2', '=', $request->nota);

                        $q2 = DB::table('rs49')
                            ->join('rs51', 'rs49.rs1', '=', 'rs51.rs4')
                            ->selectRaw("'x' as flag, rs51.id as id_trans, rs51.rs2 as nota, rs51.rs3 as tgl, rs51.rs8 as kodedokter,
                                        rs49.rs21 as keterangan, (rs51.rs6+rs51.rs13) as biaya,
                                        rs51.rs5 as jml, ((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotal")
                            ->where('rs49.rs21', '<>', '')
                            ->where('rs51.rs1', '=', $request->noreg)
                            ->where('rs51.rs2', '=', $request->nota)
                            ->groupBy('rs49.rs21', 'rs51.id', 'rs51.rs2', 'rs51.rs3', 'rs51.rs8', 'rs51.rs6', 'rs51.rs13', 'rs51.rs5');

                            $val = $q1->unionAll($q2)->orderBy('id_trans')->get();
                            $id_trans = $val->pluck('id_trans')->implode(',');
                            $unit = 'PEN002';
                    }else if($jenis === 'Farmasi'){
                        $data = Resepkeluarheder::with(
                            [
                                'rincian.mobat:kd_obat,nama_obat,satuan_k',
                                'rincianracik.mobat:kd_obat,nama_obat,satuan_k',
                                'retur' => function ($ret) {
                                    $ret->with([
                                            'rinci'
                                        ]);
                                },
                            ]
                        )
                        ->where('noreg', $request->noreg)
                        ->whereIn('flag', ['3', '4'])
                        ->get();
                        $id_trans = $request->nota;
                        $unit     = $data->first()?->depo;
                    }else if($jenis === 'Radiologi'){
                        $val = DB::table('rs47')
                            ->join('rs48', 'rs47.rs1', '=', 'rs48.rs4')
                            ->selectRaw("GROUP_CONCAT(rs48.id SEPARATOR ',') AS id_trans, rs47.rs2 AS keterangan, SUM((rs48.rs6+rs48.rs13)*rs48.rs5) AS subtotal")
                            ->where('rs48.rs1', '=', $request->noreg)
                            ->where('rs48.rs2', '=', $request->nota)
                            ->groupBy('rs47.rs2')
                            ->get();
                        $unit = 'PEN003';
                        $id_trans = $val->pluck('id_trans')->implode(',');
                    }else if($jenis === 'Operasi'){
                        $val = DB::table('rs54')
                            ->join('rs53', 'rs53.rs1', '=', 'rs54.rs4')
                            ->selectRaw("GROUP_CONCAT(rs54.id SEPARATOR ',') AS id_trans")
                            ->selectRaw("SUM((rs54.rs5+rs54.rs6+rs54.rs7)*rs54.rs8) AS subtotal")
                            ->where('rs54.rs2', trim($request->nota))
                            ->where('rs54.rs1', $request->noreg)
                            ->get();
                        $unit = 'PEN001';
                        $id_trans = $val->pluck('id_trans')->implode(',');
                    }else if($jenis === 'TindakanPsikologi'){
                        $val = DB::table('psikologi_trans')
                         ->selectRaw("GROUP_CONCAT(psikologi_trans.id SEPARATOR ',') AS id_trans")
                         ->selectRaw("SUM((psikologi_trans.rs7+psikologi_trans.rs13)*psikologi_trans.rs5) AS subtotal")
                            ->selectRaw("psikologi_trans.rs22 AS kd_jenis")
                            ->selectRaw("rs19.rs2 AS jenis")
                            ->leftJoin('rs19', 'psikologi_trans.rs22', '=', 'rs19.rs1')
                            ->where('psikologi_trans.rs1', $request->noreg)
                            ->where('psikologi_trans.rs2', $request->nota)
                            ->groupBy('psikologi_trans.rs22')
                            ->get();
                        $id_trans = $val->pluck('id_trans')->implode(',');
                        $unit = $val->pluck('kd_jenis')->first();
                    }else if($jenis === 'SharingBpjs'){
                        $id_trans = $request->nota;
                        $unit = $request->kodepoli;
                        $jenis = 'SHARING BPJS';
                    }
                    // foreach ($val as $row) {
                    $insertkwitansi_d =    Kwitansidetail::create([
                            'no_pembayaran' => '-',
                            'no_kwitansi'   => $nokwitansi,
                            'id_trans'      => $id_trans,
                            'noreg'         => $request->noreg,
                            'pelayanan'     => 'RAJAL',
                            'jenis'         => $jenis,
                            'unit'          => $unit,
                            'jml'           => $request->total,
                            'nota'          => $request->nota,
                        ]);
                   // }

                    if (!$insertkwitansi_d) {
                        return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
                    }
                }
            DB::commit();
            $hasil = kwitansilog::with(
                [
                    'rincian',
                    'pegawai:kdpegsimrs,nama'
                ]
            )->where('nokwitansi', $nokwitansi)->get();
            return new JsonResponse(['message' => 'Data Berhasil Disimpan...!!!','id_trans' => $id_trans,'kwitansi' => $hasil], 200);
        }catch(\Exception $th){
            DB::rollBack();
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!',  'id_trans' => $id_trans,'result' => $th->getMessage()], 500);
        }
    }

    public function pembayarankarcis(Request $request)
    {
        $cek = Karcis::with(
            [
                'rincian'
            ]
        )->where('batal', '!=', '1')->where('noreg', $request->noreg)->count();
        if($cek > 0){
            return new JsonResponse(['message' => 'Karcis Sudah Pernah Dicetak...!!!'], 500);
        }
        // ini buat test inject nota karcis
        // $datakarcis = Karcis::with([
        //                 'rincian',
        //                 'pegawai:kdpegsimrs,nama'
        //             ])->where('noreg', '151610/08/2025/J')->limit(1)->get();
        // return new JsonResponse(['kwitansikarcis' => $datakarcis]);

        if($request->carabayar === 'Tunai'){
            try{
                DB::beginTransaction();
                    DB::select('call karcisrj(@nomor)');
                    $x = DB::table('rs1')->select('karcisrj')->get();
                    $wew = $x[0]->karcisrj;
                    $nokarcis = FormatingHelper::karcisrj($wew, 'KRJ');

                    $simpankarcis = self::simpanpembayarankarcis($request, $nokarcis);
                    if ($simpankarcis == 500) {
                        return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
                    }

               DB::commit();
                    $datakarcis = Karcis::with([
                        'rincian',
                        'pegawai:kdpegsimrs,nama'
                    ])->where('noreg', $request->noreg)->orderBy('id', 'desc')->limit(1)
                    ->get();
                    return new JsonResponse(
                        [
                            'message' => 'Data Berhasil Disimpan',
                            'kwitansikarcis' => $datakarcis
                        ],
                        200
                    );
            }catch(\Exception $th){
                DB::rollBack();
                return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $th->getMessage()], 500);
            }
        }else if($request->carabayar === 'Qris'){
                try{
                   DB::beginTransaction();
                        $pembayaranqris = self::pembayaranqris($request, $request->noreg);
                          return $pembayaranqris;
                        if($pembayaranqris == 500){
                            return new JsonResponse(['message' => 'Membuat QRIS Gagal...!!!'], 500);
                        }
                DB::commit();
                        return new JsonResponse(['message' => 'Data Berhasil Disimpan...!!!'], 200);
                }catch(\Exception $th){
                    DB::rollBack();
                    return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $th->getMessage()], 500);
                }
        }else if($request->carabayar === 'VA'){
            try{
                DB::beginTransaction();
                        $pembayaranva = self::pembayaranqris($request, $request->noreg);

                        if($pembayaranva == 500){
                            return new JsonResponse(['message' => 'Membuat QRIS Gagal...!!!'], 500);
                        }
                DB::commit();
                         return new JsonResponse(
                            [
                                'message' => 'Data Berhasil Disimpan...!!!',
                                'result' => $pembayaranva
                            ], 200);
            }catch(\Exception $th){
                DB::rollBack();
                return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $th->getMessage()], 500);
            }
        }
    }

    public static function pembayaranqris($request, $nokarcis)
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);
        if($request->carabayar === 'Qris'){
            $jatimresp = bridgingbankjatimHelper::createqris($request);
            $xxx = $jatimresp->responsCode;
            if ($xxx == '00') {
                $total = $request->total;
                $bj = 0.4;
                $status = '';
                if ($jatimresp->status == '1') {
                    $status = 'true';
                }
                $simpanjatimresp = Tagihannontunai::firstOrCreate(
                    [
                        'rs17' => $jatimresp->invoice_number
                    ],
                    [
                        'rs1' => $request->noreg,
                        'rs2' => $request->nama,
                        'rs3' => $request->norm,
                        'rs4' => $nokarcis,
                        'rs5' => date('Y-m-d H:i:s'),
                        'rs6' =>  date('Y-m-d'),
                        'rs7' => $user,
                        'rs8' => $total,
                        'rs9' => $total,
                        'rs10' => 'KASIR RAJAL',
                        'rs11' => 'KARCIS',
                        'rs13' => $status,
                        'rs15' => $jatimresp->qrValue,
                        'rs16' => $jatimresp->nmid,
                        'rs18' => $bj,
                        'rs19' => $jatimresp->totalAmount,
                    ]
                );
                if (!$simpanjatimresp) {
                    return 500;
                }
                return $jatimresp;
            } else {
                return 500;
            }
        }else{
            $jatimresp = bridgingbankjatimHelper::createva($request);
            $koversi = json_decode(json_encode($jatimresp), true);
            $xxx = $koversi['response']['Status']['ResponseCode']; //
            if ($xxx == '00') {
                $simpanjatimresp = Tagihannontunai::firstOrCreate(
                    [
                        'rs1' => $request->noreg,
                        'rs2' => $request->nama,
                        'rs3' => $request->norm,
                        'rs4' => $koversi['response']['VirtualAccount'], // array
                        'rs5' => date('Y-m-d H:i:s'),
                        'rs6' => $jatimresp['tglkadaluarsax'],
                        'rs7' => $user,
                        'rs8' => $request->total,
                        'rs9' => $request->total,
                        'rs10' => 'KASIR RAJAL',
                        'rs11' => 'KARCIS',
                       'rs13' => $koversi['response']['Status']['IsError'], // array
                        // 'rs15' => $jatimresp->qrValue,
                        // 'rs16' => $jatimresp->nmid,
                        // 'rs18' => $bj,
                        // 'rs19' => $jatimresp->totalAmount,
                    ]
                );
                if (!$simpanjatimresp) {
                    return 500;
                }
                return $jatimresp;
            } else {
                return 500;
            }
        }
    }

    public static function simpanpembayarankarcis($request, $nokarcis)
    {
        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];
        $txtrinci="";
		$rinci = Karcispoli::select('rs6', DB::raw('rs7+rs11 as jml'))->where('rs1', $request->noreg)
        ->whereIn('rs3', ['RM#', 'K2#', 'K1#', 'K3#', 'K4#', 'K5#', 'K6#'])
        ->get();
        foreach ($rinci as $val) {
            $txtrinci .= ','.$val['rs6'] . ':' . $val['jml'] . ',';
        }

        $simpankarcis = Karcis::firstOrCreate(
            [

                'nokarcis' => $nokarcis
            ],
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'tgl' => $request->tglkunjungan,
                'nama' => $request->nama,
                'sapaan' => $request->sapaan,
                'kelamin' => $request->kelamin,
                'poli' => $request->poli,
                'sistembayar' => $request->sistembayar,
                'total' => $request->total,
                'rinci' => $txtrinci,
                'carabayar' => $request->carabayar,
                'tglx' => date('Y-m-d H:i:s'),
                'users' => $kdpegsimrs ?? ''
            ]
        );
        if (!$simpankarcis) {
            return 500;
        }

        $x = ['RM#', 'K2#', 'K1#', 'K3#', 'K4#', 'K5#', 'K6#'];
        $cariid = Pembayaran::select('id', 'rs6', DB::raw('rs7+rs11 as jml'))->whereIn('rs3', $x)->where('rs1', $request->noreg)->get();
        // return $cariid;
        foreach ($cariid as $val) {
            //$wew[] = $val['jml'];
            $simpandetail = Kwitansidetail::create(
                [
                    'no_pembayaran' => '-',
                    'no_kwitansi' => $nokarcis,
                    'id_trans' => $val['id'],
                    'noreg' => $request->noreg,
                    'pelayanan' => 'RAJAL',
                    'jenis' => $val['rs6'],
                    'unit' => $request->kodepoli,
                    'jml' => $val['jml'],
                    'id_kwitansilog' => $simpankarcis['id']
                ]
            );
        }
        if (!$simpandetail) {
            return 500;
        }
        return 200;
    }

    public function batalkwitansi(Request $request)
    {
        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];
        // return $request;
        if($request->jenis == 'karcis'){
            $cek = Karcis::where('nokarcis', $request->nokwitansi)->where('batal', '1')->first();
            if($cek){
                return new JsonResponse(['message' => 'Data Sudah Dibatalkan...!!!'], 500);
            }
            $data = Karcis::where('nokarcis', $request->nokwitansi)->first();
            if ($data) {
                $data->update([
                    'batal' => '1',
                    'tgl_batal' => date('Y-m-d H:i:s'),
                    'user_batal' => $kdpegsimrs ?? ''
                ]);
                return new JsonResponse(['data' => $data,'jenis' => 'karcis','message' => 'Data Berhasil Dibatalkan...!!!'], 200);
            } else {
                return new JsonResponse(['message' => 'Data Gagal Dibatalkan...!!!'], 500);
            }
        }else{
            $cek = Kwitansilog::where('nokwitansi', $request->nokwitansi)->where('batal', '1')->first();
            if($cek){
                return new JsonResponse(['message' => 'Data Sudah Dibatalkan...!!!'], 500);
            }
            $cekx = Kwitansilog::where('nokwitansi', $request->nokwitansi)->Where('no_tbp', '!=', '')->count();
            // return $cekx;
             if($cekx > 0){
                return new JsonResponse(['message' => 'Kwitansi Sudah Dibuat TBP...!!!'], 500);
            }
            $data = Kwitansilog::where('nokwitansi', $request->nokwitansi)->first();
            if($data){
                $data->update([
                    'batal' => '1',
                    'tgl_batal' => date('Y-m-d H:i:s'),
                    'user_batal' => $kdpegsimrs ?? ''
                ]);
                return new JsonResponse(['data' => $data,'jenis' => 'kwitansi','message' => 'Data Berhasil Dibatalkan...!!!'], 200);
            }
        }
    }



}
