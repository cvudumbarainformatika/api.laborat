<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poli extends Model
{
    use HasFactory;
<<<<<<< HEAD:app/Models/Poli.php
    protected $table = 'rs19';
=======
    protected $guarded = ['id'];

    public function beritas()
    {
        return $this->belongsToMany(Berita::class, 'kategori_berita', 'category_id', 'berita_id');
    }
>>>>>>> 5a5865f34e9a0866bee773204e2d5343edd57e01:app/Models/Category.php
}
