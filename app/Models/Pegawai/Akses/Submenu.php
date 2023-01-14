<?php

namespace App\Models\Pegawai\Akses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submenu extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $guarded = ['id'];

    public function menu()
    {
        $this->belongsTo(Menu::class);
    }
}
