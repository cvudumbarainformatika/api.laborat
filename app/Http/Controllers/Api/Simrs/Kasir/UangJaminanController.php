<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\UangJaminan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UangJaminanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        $data = UangJaminan::query()
            ->select([
                'id',
                'rs1 as noreg',
                'rs2 as nota',
                'rs4 as tanggal',
                'jenis_pembayaran',
                'rs9 as no_va',
                'rs7 as jumlah',
                'rs11 as tgl_entry',
            ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where('rs9', 'like', '%' . $request->q . '%');
            })
            ->when($request->filled('from'), function ($query) use ($request) {
                $query->where('rs4', '>=', $request->from . ' 00:00:00');
            })
            ->when($request->filled('to'), function ($query) use ($request) {
                $query->where('rs4', '<=', $request->to . ' 23:59:59');
            })
            ->orderByDesc('id')
            ->paginate($perPage);

        return new JsonResponse($data);
    }

    public function pasien(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pelayanan' => ['required', 'in:igd,ranap'],
            'q' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $perPage = $data['per_page'] ?? 10;

        if ($data['pelayanan'] === 'igd') {
            $query = DB::table('rs17')
                ->leftJoin('rs15', 'rs15.rs1', '=', 'rs17.rs2')
                ->select('rs17.rs1 as noreg', 'rs17.rs2 as norm', 'rs15.rs2 as nama', 'rs17.rs3 as tanggal_masuk')
                ->selectRaw('(SELECT rs4 FROM rs297 WHERE rs297.rs1 = rs17.rs1 ORDER BY rs297.id DESC LIMIT 1) as no_va')
                ->where('rs17.rs8', 'POL014');
        } else {
            $query = DB::table('rs23')
                ->leftJoin('rs15', 'rs15.rs1', '=', 'rs23.rs2')
                ->select('rs23.rs1 as noreg', 'rs23.rs2 as norm', 'rs15.rs2 as nama', 'rs23.rs3 as tanggal_masuk')
                ->selectRaw('(SELECT rs4 FROM rs297 WHERE rs297.rs1 = rs23.rs1 ORDER BY rs297.id DESC LIMIT 1) as no_va')
                ->where('rs23.rs1', '!=', '');
        }

        $query->when($request->filled('q'), function ($builder) use ($request, $data) {
            $table = $data['pelayanan'] === 'igd' ? 'rs17' : 'rs23';
            $builder->where(function ($filter) use ($request, $table) {
                $filter->where($table . '.rs1', 'like', '%' . $request->q . '%')
                    ->orWhere($table . '.rs2', 'like', '%' . $request->q . '%')
                    ->orWhere('rs15.rs2', 'like', '%' . $request->q . '%');
            });
        });

        return new JsonResponse($query->orderByDesc('tanggal_masuk')->paginate($perPage));
    }

    public function simpan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date_format:Y-m-d H:i'],
            'jenis_pembayaran' => ['required', 'in:QRIS,VA'],
            'noreg' => ['required', 'string', 'max:20'],
            'no_va' => ['nullable', 'string', 'max:255'],
            'jumlah' => ['required', 'numeric', 'min:1'],
        ]);

        $nomorPembayaran = trim((string) ($data['no_va'] ?? ''));
        if ($data['jenis_pembayaran'] === 'VA') {
            if ($nomorPembayaran === '') {
                return new JsonResponse(['message' => 'Nomor Virtual Account wajib diisi.'], 422);
            }

            if (!DB::table('rs298')->where('rs1', $nomorPembayaran)->exists()) {
                return new JsonResponse([
                    'message' => 'Maaf nomor Virtual Account yang dimasukkan belum terflag di database SIMRS.',
                ], 422);
            }
        }

        $sessionUser = FormatingHelper::session_user();
        $kasir = substr((string) ($sessionUser['kodesimrs'] ?? ''), 0, 10);

        $uangJaminan = DB::transaction(function () use ($data, $nomorPembayaran, $kasir) {
            $counter = DB::table('rs1')->lockForUpdate()->value('puangjaminan');
            if ($counter === null) {
                throw new \RuntimeException('Counter uang jaminan tidak ditemukan.');
            }

            $nota = $this->gennota(((int) $counter) + 1, 'UJ');
            DB::table('rs1')->increment('puangjaminan');

            return UangJaminan::create([
                'rs1' => trim($data['noreg']),
                'rs2' => $nota,
                'rs3' => 'UJ#',
                'rs4' => $data['tanggal'] . ':00',
                'rs5' => 'D',
                'rs6' => 'Uang Jaminan',
                'rs7' => $data['jumlah'],
                'rs8' => '',
                'rs9' => $nomorPembayaran,
                'rs10' => $kasir,
                'rs11' => now(),
                'jenis_pembayaran' => $data['jenis_pembayaran'],
            ]);
        });

        return new JsonResponse([
            'message' => 'Uang jaminan berhasil disimpan.',
            'data' => [
                'id' => $uangJaminan->id,
                'noreg' => $uangJaminan->rs1,
                'nota' => $uangJaminan->rs2,
                'tanggal' => $uangJaminan->rs4,
                'jenis_pembayaran' => $uangJaminan->jenis_pembayaran,
                'no_va' => $uangJaminan->rs9,
                'jumlah' => $uangJaminan->rs7,
            ],
        ], 201);
    }

    private function gennota(int $number, string $kode): string
    {
        return now()->format('ymd') . '/' . str_pad($number, 4, '0', STR_PAD_LEFT) . $kode;
    }
}
