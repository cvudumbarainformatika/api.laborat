<?php

namespace App\Models\Simrs\Pelayanan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkriningKehamilan extends Model
{
    use HasFactory;
    protected $table = 'skrining_kehamilan';
    protected $guarded = ['id'];
}