<?php

namespace App\Http\Controllers\Api\Anjungan;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Pasien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AnjunganController extends Controller
{

    public function cari_rujukan()
    {
        // $rujukan = false;
        $rujukanPcare = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/' . request('search'));
        return $rujukanPcare;
    }
    public function cari_rujukan_rs()
    {
        // $rujukan = false;
        $rujukanPcare = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/' . request('search'));
        return $rujukanPcare;
    }
}
