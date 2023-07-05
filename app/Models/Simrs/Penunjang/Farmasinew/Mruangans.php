<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mruangans extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $table = 'ruangs';
    protected $guarded = ['id'];

    public function scopeRuangans($data)
    {
        return $data->select([
            'kode','uraian as ruang'
        ]);
    }

    public function scopeFilter($cari, array $reqs)
    {
        $cari->when(
            $reqs['q'] ?? false,
            function ($data, $query) {
                return $data->where('uraian', 'LIKE', '%' . $query . '%')
                    ->orderBy('uraian');
            }
        );
    }
}
