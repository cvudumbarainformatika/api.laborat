<?php

namespace App\Http\Controllers\Api\Dashboardexecutive;

use App\Http\Controllers\Controller;
use App\Models\Agama;
use App\Models\Executive\DetailPenerimaan;
use App\Models\Executive\HeaderPenerimaan;
use App\Models\Executive\KeuTransPendapatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KeuanganController extends Controller
{
    public function pendapatan()
    {
        // $transaksiPendapatan = KeuTransPendapatan::where('noTrans', 'not like', "%TBP-UJ%")
        //     ->whereMonth('tgl', request('month'))
        //     ->whereYear('tgl', request('year'))->sum('nilai');

        // $penerimaan = DetailPenerimaan::hasByNonDependentSubquery('header_penerimaan', function ($q) {
        //     $q->whereYear('rs2', request('year'))
        //         ->where('setor', '=', 'Setor')
        //         ->where(function ($query) {
        //             $query->whereNull('tglBatal')
        //                 ->orWhere('tglBatal', '=', '0000-00-00 00:00:00');
        //         });
        // })->with('header_penerimaan')
        //     ->sum('rs4');

        // $penerimaan2 = DetailPenerimaan::hasByNonDependentSubquery('header_penerimaan', function ($q) {
        //     $q->whereYear('rs2', request('year'))
        //         ->where('setor', '<>', 'Setor')
        //         ->where(function ($query) {
        //             $query->whereNull('tglBatal')
        //                 ->orWhere('tglBatal', '=', '0000-00-00 00:00:00');
        //         })
        //         ->whereHas('keu_trans_setor');
        // })->with('header_penerimaan')
        //     ->sum('rs4');

        // $data = array(
        //     'transaksi_pendapatan' => $transaksiPendapatan,
        //     'penerimaan' => $penerimaan,
        //     'penerimaan2' => $penerimaan2
        // );

        $tgl = request('year') . "-" . "01-01";
        $tglx = request('year') . "-" . request('month') . "-31";

        $data = DB::select("select sum(penerimaan) as penerimaan from (
									select
										tgl,
										noRek,
										concat(ket,' (',noTrans,')') uraian,
										nilai penerimaan,
										0 pengeluaran,
										0 saldo,
										1 urut
									from
										keu_trans_pendapatan
									where
										tgl>='" . $tgl . "'
										and tgl<='" . $tglx . "'
										and noTrans not like '%TBP-UJ%'
									union all

									select
										rs258.rs2 tgl,
										rs258.noRek,
										concat(rs260.ket,' (',rs258.rs1,')') uraian,
										rs260.rs4 penerimaan,
										0 pengeluaran,
										0 saldo,
										1 urut
									from
										rs258,
										rs260
									where
										rs258.rs1=rs260.rs1
										and rs258.rs2>='" . $tgl . "'
										and rs258.rs2<='" . $tglx . "'
										and setor='Setor'
										and (rs258.tglBatal is null or rs258.tglBatal='0000-00-00 00:00:00')
									union all


									select tgl,noRek,uraian,sum(penerimaan) penerimaan,pengeluaran,saldo,urut from (
									select
										keu_trans_setor.noSetor,
										keu_trans_setor.tgl,
										rs258.noRek,
										concat(keu_trans_setor.ket,' (',keu_trans_setor.noSetor,')') uraian,
										rs260.rs4 penerimaan,
										0 pengeluaran,
										0 saldo,
										1 urut
									from
										rs258,
										rs260,
										keu_trans_setor
									where
										rs258.rs1=rs260.rs1
										and keu_trans_setor.noSetor = rs258.noSetor
										and keu_trans_setor.tgl>='" . $tgl . "'
										and keu_trans_setor.tgl<='" . $tglx . "'
										and setor<>'Setor'
										and (rs258.tglBatal is null or rs258.tglBatal='0000-00-00 00:00:00')
									union all



									select
										keu_trans_setor.noSetor,
										keu_trans_setor.tgl,
										keu_trans_setor.noRek,
										concat(ket,' (',keu_trans_setor.noSetor,')') uraian,
										tbp.nilai penerimaan,
										0 pengeluaran,
										0 saldo,
										1 urut
									from
										keu_trans_setor,
										tbp
									where
										tbp.noSetor=keu_trans_setor.noSetor
										and keu_trans_setor.tgl>='" . $tgl . "'
										and keu_trans_setor.tgl<='" . $tglx . "'
										and tbp.setor<>'Setor'
									) as vTunai group by noSetor
									union all
									select
										keu_trans_setor.tgl,
										keu_trans_setor.noRek,
										concat(ket,' (',keu_trans_setor.noSetor,')') uraian,
										tbpuj.nilai penerimaan,
										0 pengeluaran,
										0 saldo,
										1 urut
									from
										keu_trans_setor,
										tbpuj
									where
										tbpuj.noSetor=keu_trans_setor.noSetor
										and keu_trans_setor.tgl>='" . $tgl . "'
										and keu_trans_setor.tgl<='" . $tglx . "'
										and tbpuj.setor<>'Setor'
									union all
									select
										tglTrans tgl,
										noRekPengirim noRek,
										concat(ket,' (',idTrans,')') uraian,
										0 penerimaan,
										nilai pengeluaran,
										0 saldo,
										2 urut
									from
										keu_trans_bk
									where
										tglTrans>='" . $tgl . "'
										and tglTrans<='" . $tglx . "'
										and (batal is null or batal='')
									union all
									select
										tglTrans tgl,
										noRek,
										concat(ket,' (',id,')') uraian,
										0 penerimaan,
										nominal pengeluaran,
										0 saldo,
										2 urut
									from
										keu_bp_pph
									where
										tglTrans>='" . $tgl . "'
										and tglTrans<='" . $tglx . "'
										and (batal is null or batal='')
									union all
									select
										date(tanggalpenerimaan) tgl,
										noRek,
										concat(keterangan,' (',nomorpenerimaan,')') uraian,
										0 penerimaan,
										nominal pengeluaran,
										0 saldo,
										2 urut
									from
										penerimaandaribank
									where
										tanggalpenerimaan>='" . $tgl . "'
										and tanggalpenerimaan<='" . $tglx . "'
								) as vBku order by tgl,urut");

        return response()->json($data);
    }
}
