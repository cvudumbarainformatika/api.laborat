<?php

namespace App\Http\Controllers\Api\Antrean;

use App\Http\Controllers\Controller;
use App\Models\Antrean\Booking;
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

    public function calling_layanan(Request $request)
    {
        return response()->json($request->all());
    }
}
