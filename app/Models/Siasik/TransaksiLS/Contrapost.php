<?php

namespace App\Models\Siasik\TransaksiLS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contrapost extends Model
{
    use HasFactory;
    protected $connection = 'siasik';
    protected $guarded = ['id'];
    protected $table = 'contrapost';
    protected $timestamp = false;


}
