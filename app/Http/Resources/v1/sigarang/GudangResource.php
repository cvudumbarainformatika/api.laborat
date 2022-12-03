<?php

namespace App\Http\Resources\v1\sigarang;

use Illuminate\Http\Resources\Json\JsonResource;

class GudangResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'utama' => $this->utama,
            'depo' => $this->depo,
            'ruang' => $this->ruang,
            'kode' => $this->kode,
            'nama' => $this->nama,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
