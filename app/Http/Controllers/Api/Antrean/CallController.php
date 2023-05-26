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
        $data = Booking::where('statuscetak', 1)
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
        $unit = Unit::find($request->unit_id);
        $message = array(
            'nomorantrean' => $request->nomorantrean,
            'kodebooking' => $request->kodebooking,
            'layanan_id' => $request->layanan_id,
            'namapasien' => $request->namapasien,
            'unit' => $unit
        );

        $cek = Panggil::where('display', $unit->display_id)->count();

        $resp = [
            'code' => 202,
            'message' => 'Maaf Ada Panggilan Lain'
        ];
        if ($cek > 0) {
            return response()->json($resp, 200);
        }

        //memasukkan panggilan

        event(new AntreanEvent($message));
        return response()->json(['data' => $request->all()], 200);
        // return response()->json($request->all());
    }
}
