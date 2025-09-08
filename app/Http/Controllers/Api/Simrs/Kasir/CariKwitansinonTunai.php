<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Paymentbankjatim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CariKwitansinonTunai extends Controller
{
    public function getKwitansinontunai()
    {
        $noreg = request('noreg');
        $data = Paymentbankjatim::where('purposetrx', $noreg)->get();
        return new JsonResponse(['data' => $data], 200);
    }
}
