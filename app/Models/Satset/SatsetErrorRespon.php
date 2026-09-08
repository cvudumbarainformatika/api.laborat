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
            // Auto detect jenis if empty
            if (empty($model->jenis) && !empty($model->uuid)) {
                $uuidStr = (string) $model->uuid;
                if (str_ends_with(strtolower($uuidStr), '/j')) {
                    $model->jenis = 'rajal';
                } elseif (str_ends_with(strtolower($uuidStr), '/i')) {
                    $model->jenis = 'ranap';
                } elseif (str_ends_with(strtolower($uuidStr), '/x')) {
                    $model->jenis = 'igd';
                } else {
                    $rajal = DB::table('rs17')->where('rs1', $uuidStr)->first(['rs1', 'rs8']);
                    if ($rajal) {
                        if ($rajal->rs8 === 'PEN005') {
                            $model->jenis = 'hd';
                        } elseif ($rajal->rs8 === 'POL014') {
                            $model->jenis = 'igd';
                        } else {
                            $model->jenis = 'rajal';
                        }
                    } elseif (DB::table('rs23')->where('rs1', $uuidStr)->exists()) {
                        $model->jenis = 'ranap';
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
