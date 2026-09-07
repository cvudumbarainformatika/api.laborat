<?php

namespace App\Models\Satset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

class Satset extends Model
{
    use HasFactory;
    protected $table = 'satsets';
    protected $guarded = ['id'];
    protected $casts = [
        'response' => 'array'
    ];

    protected static function booted()
    {
        static::saving(function ($model) {
            if (empty($model->jenis) && !empty($model->uuid)) {
                $uuid = (string) $model->uuid;
                // 1. Jika dari rs23 -> Pasti Ranap
                $isRanap = DB::table('rs23')->where('rs1', $uuid)->exists();
                if ($isRanap) {
                    $model->jenis = 'ranap';
                    return;
                }

                // 2. Jika dari rs17
                $rajal = DB::table('rs17')->where('rs1', $uuid)->first(['rs1', 'rs8']);
                if ($rajal) {
                    if ($rajal->rs8 === 'POL014' || str_ends_with(strtolower($rajal->rs1), '/x')) {
                        $model->jenis = 'igd';
                    } else {
                        $model->jenis = 'rajal';
                    }
                    return;
                }

                // 3. Fallback jika tidak ditemukan di rs17/rs23
                if (str_ends_with(strtolower($uuid), '/i')) {
                    $model->jenis = 'ranap';
                } elseif (str_ends_with(strtolower($uuid), '/x')) {
                    $model->jenis = 'igd';
                } else {
                    $model->jenis = 'rajal';
                }
            }
        });
    }
}
