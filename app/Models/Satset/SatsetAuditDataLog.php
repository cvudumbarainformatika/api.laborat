<?php

namespace App\Models\Satset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SatsetAuditDataLog extends Model
{
    use HasFactory;

    protected $table = 'satset_audit_data_log';
    protected $guarded = ['id'];

    protected $casts = [
        'data_simrs' => 'array',
        'data_pembanding' => 'array',
        'tgl_perbaikan' => 'datetime',
    ];

    /**
     * Catat audit log secara aman tanpa membebani / menghentikan proses bridging.
     * Mencegah duplikasi data log yang masih berstatus PENDING.
     */
    public static function recordAudit(array $data)
    {
        try {
            $kategori = $data['kategori'] ?? null;
            $refId = $data['ref_id'] ?? null;

            if (empty($kategori) || empty($refId)) {
                return null;
            }

            $existing = self::where('kategori', $kategori)
                ->where('ref_id', $refId)
                ->where('status_perbaikan', 'PENDING')
                ->first();

            if ($existing) {
                $existing->update([
                    'unit' => $data['unit'] ?? $existing->unit,
                    'noreg' => $data['noreg'] ?? $existing->noreg,
                    'nama' => $data['nama'] ?? $existing->nama,
                    'nik_simrs' => $data['nik_simrs'] ?? $existing->nik_simrs,
                    'nik_valid' => $data['nik_valid'] ?? $existing->nik_valid,
                    'data_simrs' => $data['data_simrs'] ?? $existing->data_simrs,
                    'data_pembanding' => $data['data_pembanding'] ?? $existing->data_pembanding,
                    'keterangan' => $data['keterangan'] ?? $existing->keterangan,
                ]);
                return $existing;
            }

            return self::create($data);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
