<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
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
        // $validator = Validator::make($request->all(), [
        //     'KODE_PRODUCT' => 'required',
        //     'IS_CITO' => 'required',
        //     'HASIL' => 'required',
        // ]);
        // if ($validator->fails()) {
        //     return response()->json($validator->errors(), 422);
        // }

        $request->validate([
            'KODE_PRODUCT'=> 'required',
            'HASIL'=> 'required',
            'IS_CITO'=> 'required',
            // 'password'=> 'required',
        ]);
       return response()->json(['message'=>'success'], 201);
    }
}
