<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Pacs extends Model
{
  use HasFactory;
  protected $table = 'rs48_pacs';
  protected $guarded = ['id'];

  // public $timestamps = false; // <--- Ini menonaktifkan created_at & updated_at
}
