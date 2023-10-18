<?php

namespace App\Models\Sigarang\Transaksi\Penerimaanruangan;

use App\Models\Sigarang\Barang108;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Satuan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailsPenerimaanruangan extends Model
{
    use HasFactory;
    protected $connection = 'sigarang';
    protected $guarded = ['id'];


    public function satuan()
    {
        return  $this->belongsTo(Satuan::class, 'kode_satuan', 'kode');
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }

    public function barangrs()
    {
        return  $this->belongsTo(BarangRS::class, 'kode_rs', 'kode')->withTrashed();
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }
    public function barang108()
    {
        return  $this->belongsTo(Barang108::class, 'kode_rs', 'kode');
        // return $this->belongsTo(BarangRS::class, 'kode', 'kode_rs');
    }

    public function penerimaanruangan()
    {
        return $this->belongsTo(Penerimaanruangan::class);
    }

    // public function hargastok()
    // {
    //     return $this->hasOneThrough(
    //         RecentStokUpdate::class,
    //         BarangRS::class,
    //         'no_penerimaan',
    //         'no_penerimaan',
    //         'kode_rs',
    //         'kode',
    //     )->withTrashed();
    // }
}


// mechanics => det
//     id - integer = kode_rs
//     name - string

// cars => baran
//     id - integer =>kode
//     model - string
//     mechanic_id - integer

// owners
//     id - integer
//     name - string
//     car_id - integer

// public function carOwner()
//     {
//         return $this->hasOneThrough(
//             Owner::class,
//             Car::class,
//             'mechanic_id', // Foreign key on the cars table...
//             'car_id', // Foreign key on the owners table...
//             'id', // Local key on the mechanics table...
//             'id' // Local key on the cars table...
//         );
//     }
