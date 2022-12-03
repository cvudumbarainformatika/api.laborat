<?php

namespace App\Models\Sigarang;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];


    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('nama', 'LIKE', '%' . $query . '%');
        });
    }
}
