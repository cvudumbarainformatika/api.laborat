<?php

namespace App\Http\Controllers\Api\Simrs\HomeCare;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class CssdRequestController extends Controller
{
    private const CSSD_STOCK_ROOM = 'PEN008';

    public function barang(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori' => ['required', Rule::in(['instrumen', 'kassa'])],
            'q' => ['required', 'string', 'min:1'],
        ]);

        $query = DB::table('barang_cssd as barang')
            ->join('stok_cssd as stok', 'stok.kode', '=', 'barang.kode')
            ->where('stok.ruangan', self::CSSD_STOCK_ROOM)
            ->where('stok.stok', '>', 0)
            ->where(function (Builder $query) use ($validated) {
                $term = '%' . $validated['q'] . '%';
                $query->where('barang.kode', 'like', $term)
                    ->orWhere('barang.nama', 'like', $term);
            })
            ->select('barang.kode as kodebarang', 'barang.nama', 'barang.jenis', 'stok.stok')
            ->orderBy('barang.nama')
            ->limit(20);

        $this->applyCategory($query, $validated['kategori']);

        return new JsonResponse(['data' => $query->get()]);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori' => ['required', Rule::in(['instrumen', 'kassa'])],
        ]);
        $pegawai = $this->pegawaiPengguna();

        $query = DB::table('permintaan_cssd_header')
            ->where('ruangan', $pegawai->kode_ruang)
            ->orderByDesc('tgl_trans')
            ->limit(100);
        $this->applyHeaderCategory($query, $validated['kategori']);

        return new JsonResponse([
            'ruangan' => ['kode' => $pegawai->kode_ruang],
            'data' => $query->get([
                'nopermintaan', 'tgl_trans', 'ruangan', 'user', 'jenis_barang', 'layani',
            ]),
        ]);
    }

    public function show(string $nopermintaan): JsonResponse
    {
        $pegawai = $this->pegawaiPengguna();
        $header = DB::table('permintaan_cssd_header')
            ->where('nopermintaan', $nopermintaan)
            ->where('ruangan', $pegawai->kode_ruang)
            ->first();

        if (!$header) {
            return new JsonResponse(['message' => 'Permintaan tidak ditemukan untuk ruangan Anda'], 404);
        }

        return new JsonResponse([
            'header' => $header,
            'details' => $this->details($nopermintaan),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kategori' => ['required', Rule::in(['instrumen', 'kassa'])],
            'kodebarang' => ['required', 'string', 'max:50'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'nopermintaan' => ['nullable', 'string', 'max:50'],
        ]);
        $pegawai = $this->pegawaiPengguna();

        $barangQuery = DB::table('barang_cssd as barang')
            ->join('stok_cssd as stok', 'stok.kode', '=', 'barang.kode')
            ->where('stok.ruangan', self::CSSD_STOCK_ROOM)
            ->where('barang.kode', $validated['kodebarang'])
            ->select('barang.kode as kodebarang', 'barang.nama', 'barang.jenis', 'stok.stok');
        $this->applyCategory($barangQuery, $validated['kategori']);
        $barang = $barangQuery->first();

        if (!$barang) {
            return new JsonResponse(['message' => 'Barang tidak tersedia untuk kategori ini'], 422);
        }
        if ((int) $barang->stok < $validated['jumlah']) {
            return new JsonResponse([
                'message' => 'Jumlah permintaan melebihi stok CSSD yang tersedia',
                'stok' => (int) $barang->stok,
            ], 422);
        }

        $nopermintaan = DB::transaction(function () use ($validated, $pegawai, $barang) {
            $nopermintaan = trim($validated['nopermintaan'] ?? '');

            if ($nopermintaan === '') {
                DB::statement('CALL permintaancssd(@nomor)');
                $nomor = DB::selectOne('SELECT @nomor AS nomor');
                if (!$nomor || $nomor->nomor === null) {
                    throw new RuntimeException('Nomor permintaan CSSD gagal dibuat');
                }

                $nopermintaan = 'RQ' . ((int) $nomor->nomor + 1);
                DB::table('permintaan_cssd_header')->insert([
                    'nopermintaan' => $nopermintaan,
                    'tgl_trans' => now(),
                    'ruangan' => $pegawai->kode_ruang,
                    'user' => $pegawai->nama,
                    'flag' => '1',
                    'jenis_barang' => $barang->jenis,
                ]);
            } else {
                $header = DB::table('permintaan_cssd_header')
                    ->where('nopermintaan', $nopermintaan)
                    ->where('ruangan', $pegawai->kode_ruang)
                    ->lockForUpdate()
                    ->first();

                if (!$header) {
                    abort(404, 'Permintaan tidak ditemukan untuk ruangan Anda');
                }
                if (!empty($header->layani)) {
                    abort(409, 'Permintaan sudah dilayani CSSD dan tidak dapat diubah');
                }
                if (($validated['kategori'] === 'kassa') !== ((string) $header->jenis_barang === '3')) {
                    abort(422, 'Kategori barang tidak sesuai dengan permintaan');
                }
            }

            $alreadyAdded = DB::table('permintaan_cssd_rinci')
                ->where('nopermintaan', $nopermintaan)
                ->where('kodebarang', $barang->kodebarang)
                ->exists();
            if ($alreadyAdded) {
                abort(422, 'Barang ini sudah ada pada permintaan tersebut');
            }

            DB::table('permintaan_cssd_rinci')->insert([
                'nopermintaan' => $nopermintaan,
                'kodebarang' => $barang->kodebarang,
                'tgl_entry' => now(),
                'jumlah' => $validated['jumlah'],
                'user' => $pegawai->nama,
            ]);

            return $nopermintaan;
        });

        $header = DB::table('permintaan_cssd_header')
            ->where('nopermintaan', $nopermintaan)
            ->where('ruangan', $pegawai->kode_ruang)
            ->first();

        return new JsonResponse([
            'message' => 'Barang berhasil ditambahkan ke permintaan CSSD',
            'header' => $header,
            'details' => $this->details($nopermintaan),
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $pegawai = $this->pegawaiPengguna();
        $detail = DB::table('permintaan_cssd_rinci')->where('id', $id)->first();
        if (!$detail) {
            return new JsonResponse(['message' => 'Rincian barang tidak ditemukan'], 404);
        }

        DB::transaction(function () use ($detail, $pegawai) {
            $header = DB::table('permintaan_cssd_header')
                ->where('nopermintaan', $detail->nopermintaan)
                ->where('ruangan', $pegawai->kode_ruang)
                ->lockForUpdate()
                ->first();

            if (!$header) {
                abort(404, 'Permintaan tidak ditemukan untuk ruangan Anda');
            }
            if (!empty($header->layani)) {
                abort(409, 'Permintaan sudah dilayani CSSD dan tidak dapat diubah');
            }

            DB::table('permintaan_cssd_rinci')->where('id', $detail->id)->delete();
        });

        return new JsonResponse(['message' => 'Barang berhasil dihapus dari permintaan']);
    }

    private function pegawaiPengguna(): Pegawai
    {
        $pegawaiId = auth()->user()->pegawai_id ?? null;
        $pegawai = $pegawaiId ? Pegawai::find($pegawaiId) : null;

        if (!$pegawai || empty($pegawai->kode_ruang)) {
            abort(403, 'Akun Anda belum memiliki ruangan pengaju CSSD');
        }

        return $pegawai;
    }

    private function applyCategory(Builder $query, string $kategori): void
    {
        if ($kategori === 'kassa') {
            $query->where('barang.jenis', '3');
            return;
        }

        $query->where('barang.jenis', '<>', '3');
    }

    private function applyHeaderCategory(Builder $query, string $kategori): void
    {
        if ($kategori === 'kassa') {
            $query->where('jenis_barang', '3');
            return;
        }

        $query->where(function (Builder $query) {
            $query->whereNull('jenis_barang')
                ->orWhere('jenis_barang', '<>', '3');
        });
    }

    private function details(string $nopermintaan)
    {
        return DB::table('permintaan_cssd_rinci as rincian')
            ->join('barang_cssd as barang', 'barang.kode', '=', 'rincian.kodebarang')
            ->where('rincian.nopermintaan', $nopermintaan)
            ->orderBy('rincian.tgl_entry')
            ->get([
                'rincian.id',
                'rincian.nopermintaan',
                'rincian.tgl_entry',
                'rincian.kodebarang',
                'barang.nama',
                'rincian.jumlah',
            ]);
    }
}
