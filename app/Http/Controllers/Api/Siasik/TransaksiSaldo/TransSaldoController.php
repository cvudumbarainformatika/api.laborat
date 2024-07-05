<?php

namespace App\Http\Controllers\Api\Siasik\TransaksiSaldo;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Master\RekeningBank;
use App\Models\Siasik\TransaksiSaldo\SaldoAwal_PPK;
use Illuminate\Http\JsonResponse;
// use App\Models\Sigarang\Rekening50;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransSaldoController extends Controller
{
    public function lihatrekening() {
        $saldo = RekeningBank::where('noRek', '=', '0121161061')->get();
        // ->where('noRek', request('rek'))

        return new JsonResponse( $saldo);

    }
    public function transSaldo(Request $request){
        try{
            DB::beginTransaction();
            if (!$request->has('id')){
                SaldoAwal_PPK::firstOrCreate($request->only([
                    'bulan',
                    'tahun',
                    'rekening',
                    'nilaisaldo'
                ]));
            } else {
                $data = SaldoAwal_PPK::find($request->id);
                $data->update($request->only([
                    'bulan',
                    'tahun',
                    'rekening',
                    'nilaisaldo'
                ]));
            }
            DB::commit();
            return response()->json(['message' => 'Succes'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
        // $data = SaldoAwal_PPK::create([
        // 'bulan'=> $request->bulan,
        // 'tahun' => $request->tahun,
        // 'rekening'=> $request->rekening,
        // 'nilaisaldo'=> $request->nilaisaldo,
        // ]);
        // // ($request->only('bulan','tahun','rekening','nilaisaldo'));

        // return new JsonResponse(['msg' => 'berhasil disimpan', 'data' => $data], 200);
    }
    public function hapussaldo(Request $request){
        $id=$request->id;
        $data=SaldoAwal_PPK::find($id);
        $del=$data->delete();
        if(!$del){
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
