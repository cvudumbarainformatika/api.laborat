<?php

namespace App\Http\Controllers\Api\Antrean;

use App\Events\AntreanEvent;
use App\Http\Controllers\Controller;
use App\Models\Antrean\Booking;
use App\Models\Antrean\Panggil;
use App\Models\Antrean\Unit;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CallController extends Controller
{
    public function index()
    {
        $dt = new Carbon();
        $sub = $dt->sub('0 day');
        $tgl = $sub->toDateString();

        // $tgl = '2023-05-25';

        // $os = array("1", "2", "3", "AP0001");
        // $data = Booking::whereNotIn('layanan_id', $os)
        // ->where('statuscetak', 1)
        // ->whereBetween('created_at', [$tgl . ' 00:00:00', $tgl . ' 23:59:59'])
        // ->paginate(request('per_page'));
        $data = Booking::where('statuscetak', 1)->where('layanan_id', request('unit'))
            ->whereBetween('created_at', [$tgl . ' 00:00:00', $tgl . ' 23:59:59'])
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function units()
    {
        $data = Unit::Select('id as value', 'loket as label')->orderBy('layanan_id', 'asc')->get();
        return response()->json($data);
    }

    public function calling_layanan(Request $request)
    {
        $date = new Carbon();
        $hr_ini = $date->toDateTimeString();
        $unit = Unit::find($request->unit_id);
        $data = array(
            'nomorantrean' => $request->nomorantrean,
            'kodebooking' => $request->kodebooking,
            'layanan_id' => $request->layanan_id,
            'namapasien' => $request->namapasien,
            'set' => $request->set, // 1.panggil nomor || 2.panggil nama || 3. Panggil Suara dan Nama
            'unit' => $unit
        );
        $message = array(
            'menu' => 'panggil-antrean',
            'data' => $data
        );

        $cek = Panggil::whereBetween('tanggal', [$date->toDateString() . ' 00:00:00', $date->toDateString() . ' 23:59:59'])
            ->where('display', $unit->display_id)->count();

        // return response()->json($cek, 200);
        if ($cek > 0) {
            $resp = [
                'code' => 202,
                'message' => 'Maaf Ada Panggilan Lain'
            ];
            return response()->json($resp, 200);
        }

        //kirim event ke websockets
        event(new AntreanEvent($message));
        //memasukkan panggilan
        Panggil::create(
            [
                'display' => $unit->display_id,
                'tanggal' => $hr_ini,
                'layanan_id' => $request->layanan_id,
                'nomorantrean' => $request->nomorantrean,
                'kodebooking' => $request->kodebooking,
            ]
        );

        Booking::where('kodebooking', $request->kodebooking)
            ->update(['statuspanggil' => 1]);
        $resp = [
            'code' => 200,
            'message' => 'Success'
        ];
        return response()->json($resp, 200);
    }
}
