<?php

namespace App\Http\Controllers\Api\Spo;

use App\Http\Controllers\Controller;
use App\Models\Spo\Mspo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SpoController extends Controller
{
    public function getlistspo(Request $request)
    {
        $cari = trim((string) $request->query('q', ''));
        $pemilik = trim((string) $request->query('pemilik', ''));
        $unit = trim((string) $request->query('unit', ''));

        $items = Mspo::query()
            ->select([
                'id', 'sop1 as folder', 'sop7 as kode', 'sop10 as nomor_revisi',
                'sop2 as judul', 'sop3 as file', 'sop4 as unit', 'sop8 as tanggal', 'sop9 as pemilik',
            ])
            ->when($cari !== '', function ($query) use ($cari) {
                $query->where(function ($subQuery) use ($cari) {
                    $subQuery->where('sop7', 'like', "%{$cari}%")
                        ->orWhere('sop2', 'like', "%{$cari}%")
                        ->orWhere('sop4', 'like', "%{$cari}%")
                        ->orWhere('sop9', 'like', "%{$cari}%");
                });
            })
            ->when($pemilik !== '', fn ($query) => $query->where('sop9', $pemilik))
            ->when($unit !== '', fn ($query) => $query->where('sop4', 'like', "%{$unit}%"))
            ->orderByDesc('sop8')
            ->orderByDesc('sop1')
            ->paginate(15);

        return response()->json($items);
    }

    public function units()
    {
        $units = DB::connection('spo')->table('sop1')
            ->whereNotNull('sop2')
            ->where('sop2', '<>', '')
            ->orderBy('sop2')
            ->pluck('sop2')
            ->values();

        return response()->json($units);
    }

    public function form(?int $id = null)
    {
        $item = $id ? Mspo::findOrFail($id) : null;

        return response()->json([
            'item' => $item ? $this->transform($item) : null,
            'units' => $this->units()->getData(true),
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'nomor_spo' => ['nullable', 'string', 'max:255'],
            'nomor_revisi' => ['nullable', 'string', 'max:255'],
            'nama_spo' => ['required', 'string', 'max:255'],
            'tanggal_spo' => ['required', 'date'],
            'pemilik' => ['required', 'string', 'max:255'],
            'unit_terkait' => ['nullable', 'array'],
            'unit_terkait.*' => ['string', 'max:255'],
            'dokumen' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ]);

        $isNew = empty($data['id']);
        $spo = $isNew ? new Mspo() : Mspo::findOrFail($data['id']);
        $generatedNumber = $isNew ? $this->nextNomorSpo() : null;
        $folder = $spo->sop1 ?: $generatedNumber;
        $nomorSpo = $data['nomor_spo'] ?: ($spo->sop7 ?: $generatedNumber);

        $spo->sop1 = $folder;
        $spo->sop7 = $nomorSpo;
        $spo->sop10 = $data['nomor_revisi'] ?? null;
        $spo->sop2 = $data['nama_spo'];
        $spo->sop8 = $data['tanggal_spo'];
        $spo->sop9 = $data['pemilik'];
        $spo->sop4 = implode('|', $data['unit_terkait'] ?? []);

        if ($request->hasFile('dokumen')) {
            $file = $request->file('dokumen');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('spo/uploadsxxxspo/' . $folder, $file, $filename);
            $spo->sop3 = $filename;
        }

        $spo->save();

        return response()->json([
            'message' => 'SPO berhasil disimpan',
            'item' => $this->transform($spo),
        ]);
    }

    public function destroy(int $id)
    {
        $spo = Mspo::findOrFail($id);
        $storagePath = 'spo/uploadsxxxspo/' . $spo->sop1 . '/' . $spo->sop3;
        $legacyPath = public_path($storagePath);

        if ($spo->sop3) {
            Storage::disk('public')->delete($storagePath);
            if (is_file($legacyPath)) unlink($legacyPath);
        }

        $spo->delete();

        return response()->json(['message' => 'SPO berhasil dihapus']);
    }
    private function nextNomorSpo(): string
    {
        $connection = DB::connection('spo');
        $connection->statement('CALL conter_spo(@nomor)');
        $counter = $connection->selectOne('SELECT @nomor AS nomor');
        $number = ((int) ($counter->nomor ?? 0)) + 1;

        // Setara get_nomer('SPO', $number) pada aplikasi lama.
        return 'SPO' . str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }
    private function transform(Mspo $spo): array
    {
        return [
            'id' => $spo->id,
            'folder' => $spo->sop1,
            'kode' => $spo->sop7,
            'nomor_revisi' => $spo->sop10,
            'judul' => $spo->sop2,
            'file' => $spo->sop3,
            'unit' => $spo->sop4,
            'tanggal' => $spo->sop8,
            'pemilik' => $spo->sop9,
        ];
    }
}
