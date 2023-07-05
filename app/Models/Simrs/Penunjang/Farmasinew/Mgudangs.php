<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mgudangs extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $table = 'gudangs';
    protected $guarded = ['id'];

    public function scopeGudangs($data)
    {
        return $data->select([
            'kode','nama'
        ]);
    }

    public function scopeFilter($cari, array $reqs)
    {
        $cari->when(
            $reqs['q'] ?? false,
            function ($data, $query) {
                return $data->where('nama', 'LIKE', '%' . $query . '%')
                    ->orderBy('nama');
            }
        );
    }
}
