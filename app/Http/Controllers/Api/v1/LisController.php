<?php

namespace App\Http\Controllers\Api\v1;

use App\Events\PlaygroundEvent;
use App\Http\Controllers\Controller;
use App\Models\LaboratLuar;
use App\Models\TransaksiLaborat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LisController extends Controller
{
    public function get_token(Request $request)
    {
        $xid = "4444";
        $secret_key = 'l15Test';
        date_default_timezone_set('UTC');
        // $xtimestamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $xtimestamp = strtotime($request->tanggal);
        $sign = hash_hmac('sha256', $xid . "&" . $xtimestamp, $secret_key, true);
        $xsignature = base64_encode($sign);
        return $xsignature;
    }

    public function store(Request $request)
    {
        // return response()->json($request->all(), 201);

        try {
            $request->validate([
                'ONO'=>'required',
                'GLOBAL_COMMENT'=> 'required',
                'RESULT_LIST' => 'required',
            ]);

            if ($request->GLOBAL_COMMENT === 'laborat-luar') {
                # simpan laborat luar
                // L : 13-18, P : 12-16 g/dl
                $temp = collect($request->RESULT_LIST);
                foreach ($temp as $key) {
                    LaboratLuar::where(['nota'=> $request->ONO, 'kd_lab'=> $key['ORDER_TESTID']])->update([
                        'hasil'=>$key['FLAG']." : ".$key['REF_RANGE']." ".$key['UNIT']
                    ]);
                }
            }else {
                $temp = collect($request->RESULT_LIST);
                foreach ($temp as $key) {
                    TransaksiLaborat::where(['rs2'=> $request->ONO, 'rs4'=> $key['ORDER_TESTID']])->update([
                        'rs21'=>$key['FLAG']." : ".$key['REF_RANGE']." ".$key['UNIT'],
                        'rs28'=> '1' // complete
                    ]);
                }
            }

            event(New PlaygroundEvent());
            return response()->json(['message'=>'success'], 201);
        } catch (\Throwable $th) {
            return response()->json(['message'=>'failed', $th]);
        }


    }
}
