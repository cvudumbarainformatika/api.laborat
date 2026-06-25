<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Activity;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\UserActivity;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogActivityController extends Controller
{
    public function list()
    {
        $req = [
            'order_by' => request('order_by') ?? 'created_at',
            'sort' => request('sort') ?? 'desc',
            'page' => request('page') ?? 1,
            'per_page' => request('per_page') ?? 10,
            'from' => request('from') ?? null,
            'to' => request('to') ?? null,
        ];
        $raw = UserActivity::query()
            ->with(['user:id,nip,nama,kdgroupnakes,kdpegsimrs'])
            ->when(request('q'), function ($q) {
                $q->where('action', 'like', '%' . request('q') . '%')
                    ->orWhere('description', 'like', '%' . request('q') . '%');
            })
            ->when($req['from'] != null || $req['to'] != null, function ($q) use ($req) {
                $q->whereBetween('created_at', [$req['from'] . ' 00:00:00', $req['to'] . ' 23:59:59']);
            })
            ->orderBy($req['order_by'], $req['sort']);
        $totalCount = (clone $raw)->count();
        $data = $raw->simplePaginate($req['per_page']);


        $resp = ResponseHelper::responseGetSimplePaginate($data, $req, $totalCount);
        return new JsonResponse($resp);
    }

    public function byNoreg(Request $request)
    {
        $noreg = $request->noreg;
        if (!$noreg) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Parameter noreg wajib diisi.'
            ], 400);
        }

        $orderBy = $request->order_by ?? 'created_at';
        $sort = $request->sort ?? 'desc';

        $data = UserActivity::query()
            ->with(['user:id,nip,nama,kdgroupnakes,kdpegsimrs'])
            ->where('noreg', $noreg)
            ->when($request->q, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('action', 'like', '%' . request('q') . '%')
                        ->orWhere('description', 'like', '%' . request('q') . '%');
                });
            })
            ->orderBy($orderBy, $sort)
            ->get();

        return new JsonResponse($data);
    }
}
