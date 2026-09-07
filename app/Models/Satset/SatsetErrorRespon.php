<?php

namespace App\Models\Satset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

class SatsetErrorRespon extends Model
{
    use HasFactory;
    protected $table = 'satset_error_respon';
    protected $guarded = ['id'];
    protected $casts = [
        'response' => 'array'
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            // Auto detect jenis from uuid
            if (empty($model->jenis) && !empty($model->uuid)) {
                $uuid = (string) $model->uuid;
                // 1. Jika dari rs23 -> Pasti Ranap
                $isRanap = DB::table('rs23')->where('rs1', $uuid)->exists();
                if ($isRanap) {
                    $model->jenis = 'ranap';
                } else {
                    // 2. Jika dari rs17
                    $rajal = DB::table('rs17')->where('rs1', $uuid)->first(['rs1', 'rs8']);
                    if ($rajal) {
                        if ($rajal->rs8 === 'POL014' || str_ends_with(strtolower($rajal->rs1), '/x')) {
                            $model->jenis = 'igd';
                        } else {
                            $model->jenis = 'rajal';
                        }
                    } else {
                        // 3. Fallback jika tidak ditemukan di rs17/rs23 (HANYA jika formatnya noreg SIMRS)
                        if (str_ends_with(strtolower($uuid), '/i')) {
                            $model->jenis = 'ranap';
                        } elseif (str_ends_with(strtolower($uuid), '/x')) {
                            $model->jenis = 'igd';
                        } elseif (str_ends_with(strtolower($uuid), '/j')) {
                            $model->jenis = 'rajal';
                        } else {
                            $model->jenis = null;
                        }
                    }
                }
            }

            // Auto extract error_summary if empty
            if (empty($model->error_summary) && !empty($model->response)) {
                $resp = is_array($model->response) ? $model->response : json_decode($model->response, true);
                if (is_array($resp)) {
                    if (isset($resp['issue']) && is_array($resp['issue']) && count($resp['issue']) > 0) {
                        $model->error_summary = substr($resp['issue'][0]['details']['text'] ?? $resp['issue'][0]['diagnostics'] ?? 'Error SatuSehat', 0, 255);
                    } elseif (isset($resp['message'])) {
                        $model->error_summary = substr(is_string($resp['message']) ? $resp['message'] : json_encode($resp['message']), 0, 255);
                    } elseif (isset($resp['data'])) {
                        $model->error_summary = substr(is_string($resp['data']) ? $resp['data'] : json_encode($resp['data']), 0, 255);
                    }
                } elseif (is_string($model->response)) {
                    $model->error_summary = substr($model->response, 0, 255);
                }
            }
        });
    }
}
