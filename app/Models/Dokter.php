<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dokter extends Model
{
    use HasFactory;
<<<<<<< HEAD:app/Models/Dokter.php
    protected $table = 'rs21';
=======
    protected $guarded = ['id'];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'kategori_berita', 'berita_id', 'category_id');
    }
    public function berita_views()
    {
        return $this->hasMany(BeritaView::class);
    }

    public function scopeFilter($search, array $reqs)
    {
        $search->when($reqs['q'] ?? false, function ($search, $query) {
            return $search->where('slug', 'LIKE', '%' . $query . '%')
                // ->orWhere('nip', 'LIKE', '%' . $query . '%')
                ->orWhere('judul', 'LIKE', '%' . $query . '%');
        });

        $search->when($reqs['status'] ?? false, function ($search, $sta) {
            return $search->where(['status'=>$sta]);
        });

        $search->when($reqs['category'] ?? false, function ($search, $query) {
            return $search->whereHas('categories', function($finder) use ($query) {
                if ($query !== 'all') {
                    $finder->where('url', $query);
                }

            });
        });
    }

>>>>>>> 5a5865f34e9a0866bee773204e2d5343edd57e01:app/Models/Berita.php


}
