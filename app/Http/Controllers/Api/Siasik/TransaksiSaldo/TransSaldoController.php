<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiSaldo;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Master\RekeningBank;
use App\Models\Siasik\TransaksiSaldo\SaldoAwal_PPK;
use Illuminate\Http\JsonResponse;
// use App\Models\Sigarang\Rekening50;
use Illuminate\Http\Request;

class TransSaldoController extends Controller
{
    public function lihatrekening() {
        $saldo = RekeningBank::where('noRek', '=', '0121161061')->get();
        // ->where('noRek', request('rek'))

        return new JsonResponse( $saldo);

    }
    public function transSaldo(Request $request){

        $data = SaldoAwal_PPK::create([
        'bulan'=> $request->bulan,
        'tahun' => $request->tahun,
        'rekening'=> $request->rekening,
        'nilaisaldo'=> $request->nilaisaldo,
        ]);
        // ($request->only('bulan','tahun','rekening','nilaisaldo'));

        return new JsonResponse(['msg' => 'berhasil disimpan', 'data' => $data], 200);
    }
}
