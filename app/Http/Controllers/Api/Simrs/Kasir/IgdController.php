<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IgdController extends Controller
{
    public function pasienPulang(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'q' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $from = $request->from ?? now()->toDateString();
        $to = $request->to ?? $from;
        $query = DB::table('rs17')
            ->join('rs141', 'rs141.rs1', '=', 'rs17.rs1')
            ->leftJoin('rs15', 'rs15.rs1', '=', 'rs17.rs2')
            ->leftJoin('rs9', 'rs9.rs1', '=', 'rs17.rs14')
            ->leftJoin('rs19', 'rs19.rs1', '=', 'rs17.rs8')
            ->select([
                'rs17.rs1 as noreg', 'rs17.rs2 as norm', 'rs17.rs3 as tanggal_masuk',
                'rs15.rs2 as nama', 'rs15.rs17 as kelamin', 'rs15.rs16 as tgllahir',
                'rs17.rs9 as dpjp', 'rs9.rs2 as sistem_bayar', 'rs19.rs2 as layanan',
                'rs141.created_at as tanggal_pulang', 'rs141.rs4 as status_pulang',
            ])
            ->where('rs17.rs8', 'POL014')
            ->whereRaw("UPPER(TRIM(rs9.rs2)) = 'UMUM'")
            ->whereBetween('rs141.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->when($request->q, function ($query, $q) {
                $query->where(function ($filter) use ($q) {
                    $filter->where('rs15.rs2', 'like', '%' . $q . '%')
                        ->orWhere('rs17.rs2', 'like', '%' . $q . '%')
                        ->orWhere('rs17.rs1', 'like', '%' . $q . '%');
                });
            })
            ->orderByDesc('rs141.created_at');

        return new JsonResponse($query->paginate($request->per_page ?? 10));
    }
}