<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Kasir\Kwitansilog;
use App\Models\Simrs\Kasir\Tbp;
use App\Models\Simrs\Master\Mkasir;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreateTbpController extends Controller
{
    public function masterkasir()
    {
        $data = Mkasir::whereNull('flaging')->get();
        return new JsonResponse([
            'data' => $data
        ]);
    }

    public function getdatatbp()
    {
        $to = request('tgldari') . ' 00:00:00';
        $from = request('tglsampai') . ' 23:59:59';
        if(request('jenislayanan') == 'RAJAL') {
            $jenislayanan = ['rajal'];
        } else if(request('jenislayanan') == 'RANAP'){
            $jenislayanan = ['ranap'];
        }else{
            $jenislayanan = ['rajal', 'ranap'];
        }

        $data = Tbp::join('kwitansilog', 'tbp.no_tbp', '=', 'kwitansilog.no_tbp')
            ->select(
                'tbp.no_tbp',
                DB::raw('DATE(tbp.tgl_tbp) as tgl_tbp'),
                DB::raw('DATE(tbp.tgl_terima) as tgl_terima'),
                'tbp.penyetor',
                'tbp.penerima','tbp.pelayanan',
                DB::raw('SUM(kwitansilog.total) as total'),
                'tbp.status_verif',
                'tbp.id_penyetor',
                'tbp.tgl_verif',
            )
            ->with(
                [
                    'kwitansi' => function ($query) {
                        $query->with('pegawai:kdpegsimrs,nama');
                    },
                    'pegawai:kdpegsimrs,nama'
                ])
            ->where('tbp.pelayanan', 'rajal')
            ->where('kwitansilog.batal', '')
            ->where('tbp.batal', '')
            ->whereIn('tbp.pelayanan', $jenislayanan)
              // Tambahan yang penting (dari SQL asli kamu)
            ->whereBetween('tbp.tgl_tbp', [$to, $from])
            ->when(request('q'), function ($query) {
                $query->where('tbp.no_tbp', 'like', '%' . request('q') . '%');
            })

            ->orderBy('tbp.tgl_tbp', 'desc')
            ->groupBy(
                'tbp.no_tbp',
            )
            ->paginate(request('per_page'));

        return new JsonResponse([
            'data' => $data
        ]);
    }

    public function cariKwitansi()
    {
        $dari = request('tgldari') . ' 00:00:00';
        $sampai = request('tglsampai') . ' 23:59:59';
        $user = request('kasir') ;

        if($user === 'all'){
            $user = '';
        }else{
            $user = $user;
        }

        $data = Kwitansilog::select(
                'kwitansi_d.noreg',
                'kwitansilog.norm',
                'kwitansilog.nama',
                'kwitansilog.nokwitansi',
                'kwitansilog.tglx as tgl_cetak',
                'kwitansilog.userid',
                 'kwitansilog.no_tbp',
                'kwitansilog.total'
            )
            ->join('kwitansi_d', 'kwitansi_d.no_kwitansi', '=', 'kwitansilog.nokwitansi')
            ->whereBetween('kwitansilog.tglx', [$dari, $sampai])
            ->where(function ($q) {
                $q->whereNull('kwitansilog.no_tbp')
                ->orWhere('kwitansilog.no_tbp', '');
            })
            ->where('kwitansilog.batal', '')
            ->whereIn('kwitansilog.userid', [$user])
            ->with(['pegawai'] )
            ->groupBy('kwitansi_d.no_kwitansi')
            ->orderBy('kwitansilog.tglx')
            ->get();

        // $pelayananrm = DetailbillingbytanggalController::pelayananrm($dari, $sampai);
        // $kartuidentitas = DetailbillingbytanggalController::kartuidentitas($dari, $sampai);
        // $poliklinik = DetailbillingbytanggalController::poliklinik($dari, $sampai);
        // return new JsonResponse([
        //     'data' => $data,
        //     'pelayananrm' => $pelayananrm,
        //     'kartuidentitas' => $kartuidentitas,
        //     'poliklinik' => $poliklinik
        // ]);
        // $konsulantarpoli = DetailbillingbytanggalController::konsulantarpoli($dari, $sampai);
        // $tindakan = DetailbillingbytanggalController::tindakan($dari, $sampai);
        // $laborat = DetailbillingbytanggalController::laborat($dari, $sampai);
        // $radiologi = DetailbillingbytanggalController::radiologi($dari, $sampai);
        // $onedaycare = DetailbillingbytanggalController::onedaycare($dari, $sampai);
        // $fisioterapi = DetailbillingbytanggalController::fisioterapi($dari, $sampai);
        // $hd = DetailbillingbytanggalController::hd($dari, $sampai);
        // $penunjanglain = DetailbillingbytanggalController::penunjanglain($dari, $sampai);
        // $psikologi = DetailbillingbytanggalController::psikologi($dari, $sampai);
        // $cardio = DetailbillingbytanggalController::cardio($dari, $sampai);
        // $eeg = DetailbillingbytanggalController::eeg($dari, $sampai);
        // $endoscopy = DetailbillingbytanggalController::endoscopy($dari, $sampai);
        // $obat = DetailbillingbytanggalController::farmasinew($dari, $sampai);


        $piutang = 0; //$pelayananrm + $kartuidentitas + $poliklinik ;

        return new JsonResponse([
            'data' => $data,
            'piutang' => $piutang
        ]);
    }

    public function getmasterkasir()
    {
        $data = Pegawai::select('kdpegsimrs', 'nama', 'kdkasir')->whereNotNull('kdkasir')->get();
        return new JsonResponse([
            'data' => $data
        ]);
    }

    public function createnotatbp(Request $request)
    {
        try{
            DB::beginTransaction();

                if($request->kdkasir === 'b'){
                    $cari = DB::table('rs1')->select('tbp_b')->get();
                    $countertbp = $cari[0]->tbp_b + 1;
                    DB::table('rs1')->where('id', 1)->update(['tbp_b' => $countertbp]);
                    $no_tbp = FormatingHelper::getNomorTbp('b','1.1.02.02', $countertbp);
                }else if($request->kdkasir === 'c'){
                    $cari = DB::table('rs1')->select('tbp_c')->get();
                    $countertbp = $cari[0]->tbp_c + 1;
                    DB::table('rs1')->where('id', 1)->update(['tbp_c' => $countertbp]);
                    $no_tbp = FormatingHelper::getNomorTbp('c','1.1.02.01', $countertbp);
                }else if($request->kdkasir === 'e'){
                    $cari = DB::table('rs1')->select('tbp_e')->get();
                    $countertbp = $cari[0]->tbp_e + 1;
                    DB::table('rs1')->where('id', 1)->update(['tbp_e' => $countertbp]);
                    $no_tbp = FormatingHelper::getNomorTbp('e','1.1.02.01', $countertbp);
                }elseif($request->kdkasir === 'f'){
                    $cari = DB::table('rs1')->select('tbp_f')->get();
                    $countertbp = $cari[0]->tbp_f + 1;
                    DB::table('rs1')->where('id', 1)->update(['tbp_f' => $countertbp]);
                    $no_tbp = FormatingHelper::getNomorTbp('f','1.1.02.01', $countertbp);
                }else{
                    $no_tbp = '';
                }

                if($no_tbp === ''){
                    return new JsonResponse([
                        'data' => 'TBP Tidak Bisa Di buat...!!!'
                    ]);
                }else{

                    foreach ($request->no_kwitansi as $kw) {
                        if (!empty($kw)) {
                            Kwitansilog::where('nokwitansi', $kw)->update([
                                'no_tbp' => $no_tbp
                            ]);
                        }
                    }

                    $data = Tbp::updateOrCreate(
                        [
                            'no_tbp' => $no_tbp,
                        ],[
                            'tgl_tbp' => $request->tgl_tbp,
                            'tgl_terima' => $request->tgl_terima,
                            'penyetor' => $request->kasir,
                            'penerima' => $request->namapenerima,
                            'nip_penerima' => $request->penerima,
                            'pelayanan' => $request->pelayanan,
                            'id_penyetor' => $request->kasir
                        ]
                    );
                }

            DB::commit();
                $data = self::getTbp($no_tbp);
                return new JsonResponse([
                    'message' => 'Data Berhasil Disimpan',
                    'data' => $data
                ],200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new JsonResponse([
                'data' => $th->getMessage()
            ]);
        }

    }

    public static function getTbp($no_tbp)
    {
        return Tbp::where('no_tbp', $no_tbp)
            ->with([
                'kwitansi' => function ($query) {
                    $query->select(
                        'kwitansilog.id',
                        'kwitansilog.no_tbp',        // WAJIB
                        'kwitansilog.nokwitansi',  // WAJIB
                        'kwitansilog.norm',
                        'kwitansilog.nama',
                        'kwitansilog.total',
                        'kwitansilog.userid',
                        'kwitansilog.tglx as tgl_cetak',
                        'kwitansi_d.noreg'
                    )
                    ->join(
                        'kwitansi_d',
                        'kwitansi_d.no_kwitansi',
                        '=',
                        'kwitansilog.nokwitansi'
                    )
                    ->with(['pegawai']);
                }
            ])
            ->get();
    }

    public function batalTbp(Request $request)
    {
        try{
            DB::beginTransaction();
                $update = Kwitansilog::where('nokwitansi', $request->nokwitansi)->first();
                $update->no_tbp = '';
                $update->save();
            DB::commit();
                return new JsonResponse([
                    'message' => 'Data Berhasil Dihapus'
                ],200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return new JsonResponse([
                'data' => $th->getMessage()
            ]);
        }
    }

    public function getrincianTbp()
    {
       $noTbp = request('no_tbp');

    $sql = "
    SELECT
        v_rincian.*,
        kwitansi_d.jenis AS nama_unit,
        ROUND(SUM(kwitansi_d.jml)) AS jml,
        IF(
            kwitansi_d.jenis='Tindakan',
            CONCAT(kwitansi_d.jenis,' ',v_gudang.rs2),
            v_gudang.rs2
        ) AS nama_ruang
    FROM (
        SELECT
            kwitansilog.nokwitansi,
            tbp.no_tbp,
            DATE(tbp.tgl_tbp) AS tgl_tbp,
            DATE(tbp.tgl_terima) AS tgl_terima,
            tbp.penyetor,
            tbp.penerima,
            kwitansilog.total,
            DATE(tbp.tgl_verif) AS tgl_verif,
            IF(tbp.status_verif='1','Diverifikasi','Belum Diverifikasi') AS status
        FROM tbp
        JOIN kwitansilog ON tbp.no_tbp = kwitansilog.no_tbp
        WHERE tbp.no_tbp = ?
        AND (kwitansilog.batal IS NULL OR kwitansilog.batal='' OR kwitansilog.batal='0')
    ) v_rincian
    JOIN kwitansi_d ON kwitansi_d.no_kwitansi = v_rincian.nokwitansi
    JOIN (
        SELECT rs1, rs2 FROM v_gudang
        UNION ALL SELECT 'RANAP','Rawat Inap'
        UNION ALL SELECT rs1, rs2 FROM rs150
    ) v_gudang ON v_gudang.rs1 = kwitansi_d.unit
    WHERE kwitansi_d.unit <> 'AP0002'
    GROUP BY kwitansi_d.unit

    UNION ALL

    SELECT
        v_rincian.*,
        kwitansi_d.jenis AS nama_unit,
        ROUND(SUM(kwitansi_d.jml)) AS jml,
        IF(
            kwitansi_d.jenis='Tindakan',
            CONCAT(kwitansi_d.jenis,' ',v_gudang.rs2),
            CONCAT(v_gudang.rs2,' (',v_gudangx.rs2,')')
        ) AS nama_ruang
    FROM (
        SELECT
            kwitansilog.nokwitansi,
            tbp.no_tbp,
            DATE(tbp.tgl_tbp) AS tgl_tbp,
            DATE(tbp.tgl_terima) AS tgl_terima,
            tbp.penyetor,
            tbp.penerima,
            kwitansilog.total,
            DATE(tbp.tgl_verif) AS tgl_verif,
            IF(tbp.status_verif='1','Diverifikasi','Belum Diverifikasi') AS status
        FROM tbp
        JOIN kwitansilog ON tbp.no_tbp = kwitansilog.no_tbp
        WHERE tbp.no_tbp = ?
        AND (kwitansilog.batal IS NULL OR kwitansilog.batal='' OR kwitansilog.batal='0')
    ) v_rincian
    JOIN kwitansi_d ON kwitansi_d.no_kwitansi = v_rincian.nokwitansi
    JOIN (
        SELECT rs1, rs2 FROM v_gudang
        UNION ALL SELECT 'RANAP','Rawat Inap'
        UNION ALL SELECT rs1, rs2 FROM rs150
    ) v_gudang ON v_gudang.rs1 = kwitansi_d.unit
    JOIN (
        SELECT rs1, rs2 FROM v_gudang
        UNION ALL SELECT 'RANAP','Rawat Inap'
        UNION ALL SELECT rs1, rs2 FROM rs150
    ) v_gudangx ON v_gudangx.rs1 = kwitansi_d.pelayanan
    WHERE kwitansi_d.unit IN ('Gd-05010101','Gd-04010102','AP0002')
    GROUP BY kwitansi_d.unit
    ";

    $data = DB::select($sql, [$noTbp, $noTbp]);

        return response()->json([
            'data' => $data
        ]);
    }

}
