<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GroupIdrgRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'noreg' => ['required', 'string', 'max:100'],
            'nomor_sep' => ['required', 'string', 'max:200'],
            'nomor_kartu' => ['nullable', 'string', 'max:200'],
            'nomor_rm' => ['nullable', 'string', 'max:100'],
            'nama_pasien' => ['nullable', 'string', 'max:200'],
            'tgl_lahir' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:1,2'],
            'stage' => ['nullable', 'in:1,2'],
            'jenis_rawat' => ['nullable'],
            'kelas_rawat' => ['nullable'],
            'discharge_status' => ['nullable'],
            'payor' => ['nullable', 'string', 'max:100'],
            'payor_id' => ['nullable'],
            'payor_cd' => ['nullable', 'string', 'max:100'],
            'cob_cd' => ['nullable', 'string', 'max:100'],
            'coder_nik' => ['nullable', 'string', 'max:200'],
            'topup_codes' => ['nullable', 'string'],
            'procedure' => ['nullable', 'string'],
            'prosthesis' => ['nullable', 'string'],
            'investigation' => ['nullable', 'string'],
            'drug' => ['nullable', 'string'],
        ];
    }
}
