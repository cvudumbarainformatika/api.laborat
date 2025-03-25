<?php

namespace App\Models\Simrs\Penunjang\Hemodialisa;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengkajianHemodialisa extends Model
{
    /**
     *
     * Field   Type          Null   Default              Comment
     * ------  ------------  -----  -------------------  -----------------
     * id      bigint(12)    NO     (NULL)               id
     * rs1     varchar(100)  YES    (NULL)               noreg
     * rs2     varchar(50)   YES    (NULL)               norm
     * rs3     datetime      YES    0000-00-00 00:00:00  tgl
     * rs4     varchar(50)   YES    (NULL)               gelang
     * rs5     varchar(255)  YES    (NULL)               alasan
     * rs6     varchar(25)   YES    (NULL)               riwayat
     * rs7     varchar(255)  YES    (NULL)               alasan riwayat
     * rs8     varchar(50)   YES    (NULL)               gelangx
     * rs9     varchar(255)  YES    (NULL)               alasan3 gelang
     * rs10    varchar(50)   YES    (NULL)               jenisvaskular
     * rs11    varchar(255)  YES    (NULL)               lokasi
     * rs12    varchar(10)   YES    (NULL)               tandainfeksi
     * rs13    varchar(10)   YES    (NULL)               aneurisma
     * rs14    varchar(10)   YES    (NULL)               thrill
     * rs15    varchar(10)   YES    (NULL)               bruit
     * rs16    varchar(255)  YES    (NULL)               ukuranlumen
     * rs17    varchar(255)  YES    (NULL)               venaukuranlumen
     * rs18    varchar(255)  YES    (NULL)               heparinlock
     * rs19    varchar(255)  YES    (NULL)               venaheparinlock
     * rs20    varchar(255)  YES    (NULL)               antibiotik
     * rs21    varchar(255)  YES    (NULL)               venaantibiotik
     * rs22    varchar(15)   YES    (NULL)               mesinhd
     * rs23    varchar(255)  YES    (NULL)               nomesinx
     * rs24    varchar(35)   YES    (NULL)               dialisat
     * rs25    varchar(35)   YES    (NULL)               dialiser
     * rs26    varchar(35)   YES    (NULL)               bbkering
     * rs27    varchar(255)  YES    (NULL)               lamahd
     * rs28    varchar(255)  YES    (NULL)               bloodflowrate
     * rs29    varchar(255)  YES    (NULL)               ufg
     * rs30    varchar(25)   YES    (NULL)               heparin
     * rs31    varchar(255)  YES    (NULL)               total
     * rs32    varchar(255)  YES    (NULL)               bolusawal
     * rs33    varchar(255)  YES    (NULL)               kontinyu
     * rs34    varchar(255)  YES    (NULL)               lain
     * rs35    varchar(255)  YES    (NULL)               perubahanobat

     *
     */
    use HasFactory;
    protected $table = 'rs262';
    protected $guarded = ['id'];
    public $timestamps = false;
}
