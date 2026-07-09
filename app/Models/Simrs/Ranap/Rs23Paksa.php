<?php

namespace App\Models\Simrs\Ranap;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Intervention\Image\ImageManager;

class Rs23Paksa extends Model
{
    use HasFactory;
    
    protected $table = 'rs23_paksa';
    protected $guarded = ['id'];

    public function getTtdYgMenyatakanUrlAttribute()
    {
        $image = URL::to('/storage/' . $this->ttdYgMenyatakan);
        if (!$image) {
            return null;
        }
        $handle = @fopen($image, 'r');
        if ($handle) {
            // $base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($image));
            $manager = new ImageManager();
            $base64 = (string) $manager->make($image)->resize(100, null, function ($constraint) {
                $constraint->aspectRatio();
            })->encode('data-url');

            $result =  $base64 ? $base64 : null;
            // return $this->ttdDokter ? $base64 : null;
            return $result;
        } else {
            return null;
        }
    }
}
