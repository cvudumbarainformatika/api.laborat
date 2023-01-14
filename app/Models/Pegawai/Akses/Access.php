<?php

namespace App\Models\Pegawai\Akses;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Access extends Model
{
    use HasFactory;
    protected $connection = 'kepex';
    protected $guarded = ['id'];

    public function role()
    {
        $this->belongsTo(Role::class);
    }

    public function aplikasi()
    {
        $this->belongsTo(Aplikasi::class);
    }

    public function submenu()
    {
        $this->belongsTo(Submenu::class);
    }
}
