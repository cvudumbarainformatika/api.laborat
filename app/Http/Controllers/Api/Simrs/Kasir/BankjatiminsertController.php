<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Paymentbankjatim;
use App\Models\Simrs\Kasir\Pembayarannontunai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankjatiminsertController extends Controller
{
    public function insertqrisbayar(Request $request)
    {
        $request->validate([
            'billNumber' => 'required',
            'purposetrx' => 'required',
            'storelabel' => 'required',
            'customerlabel' => 'required',
            'terminalUser' => 'required',
            'amount' => 'required',
            'core_reference' => 'required',
            'customerPan' => 'required',
            'merchantPan' => 'required',
            'invoice_number' => 'required',
            'transactionDate' => 'required'
        ]);
        $simpanpayment = Paymentbankjatim::firstOrCreate(
            [
                'billNumber' => $request->billNumber
            ],
            [
                'purposetrx' => $request->purposetrx,
                'storelabel' => $request->storelabel,
                'customerlabel' => $request->customerlabel,
                'terminalUser' => $request->terminalUser,
                'amount' => $request->amount,
                'core_reference' => $request->core_reference,
                'customerPan' => $request->customerPan,
                'merchantPan' => $request->merchantPan,
                'pjsp' => $request->pjsp,
                'invoice_number' => $request->invoice_number,
                'transactionDate' => $request->transactionDate
            ]
        );
        if (!$simpanpayment) {
            return new JsonResponse(['responsDesc' => 'Data Gagal Disimpan...!!!'], 201);
        }
        $simpanpembayaran = Pembayarannontunai::firstOrCreate(
            [
                'rs1' => $request->billNumber
            ],
            [
                'rs2' => $request->transactionDate,
                'rs3' => $request->amount,
                'rs6' => 'QRIS'
            ]
        );
        if (!$simpanpembayaran) {
            return new JsonResponse(['responsDesc' => 'Data Gagal Disimpan...!!!'], 201);
        }
        return new JsonResponse(['responsDesc' => 'Success'], 200);
    }
}
