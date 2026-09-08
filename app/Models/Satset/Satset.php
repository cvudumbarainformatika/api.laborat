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
            // Auto detect jenis if empty and uuid is a visit registration number
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
        });
    }
}
