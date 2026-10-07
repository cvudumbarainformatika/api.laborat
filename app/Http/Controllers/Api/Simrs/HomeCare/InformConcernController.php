<?php

namespace App\Http\Controllers\Api\Simrs\HomeCare;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Homecare\HomeCareKunjungan;
use App\Models\Simrs\Homecare\InformConcern;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InformConcernController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noreg' => ['required', 'string', 'exists:home_care_kunjungans,noreg'],
        ]);

        $forms = InformConcern::where('noreg', $validated['noreg'])
            ->get()
            ->keyBy('document_type');

        return new JsonResponse([
            'permohonan' => $forms->get('request'),
            'persetujuan' => $forms->get('consent'),
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'noreg' => ['required', 'string', 'exists:home_care_kunjungans,noreg'],
            'document_type' => ['required', Rule::in(['request', 'consent'])],
            'applicant_name' => ['required', 'string', 'max:150'],
            'applicant_age_gender' => ['required', 'string', 'max:100'],
            'applicant_address' => ['required', 'string'],
            'applicant_phone' => ['nullable', 'string', 'max:50'],
            'patient_relationship' => ['required', 'string', 'max:50'],
            'patient_name' => ['required', 'string', 'max:150'],
            'patient_age_gender' => ['required', 'string', 'max:100'],
            'patient_address' => ['required', 'string'],
            'service_type' => ['required_if:document_type,request', 'nullable', 'string'],
            'homecare_24_hours' => ['nullable', 'boolean'],
            'nurse_visit_frequency' => ['nullable', 'string', 'max:100'],
            'doctor_visit_frequency' => ['nullable', 'string', 'max:100'],
            'other_staff' => ['nullable', 'string', 'max:150'],
            'other_visit_frequency' => ['nullable', 'string', 'max:100'],
            'nursing_actions' => ['nullable', 'string'],
            'witness_name' => ['required', 'string', 'max:150'],
            'signer_name' => ['required', 'string', 'max:150'],
            'ttd_signer' => ['nullable', 'string', 'max:3000000'],
            'ttd_witness' => ['nullable', 'string', 'max:3000000'],
            'signed_at' => ['required', 'date'],
        ]);

        $visit = HomeCareKunjungan::where('noreg', $validated['noreg'])->firstOrFail();
        $signatures = [
            'ttd_signer' => $validated['ttd_signer'] ?? null,
            'ttd_witness' => $validated['ttd_witness'] ?? null,
        ];
        unset($validated['ttd_signer'], $validated['ttd_witness']);

        $existing = InformConcern::where('noreg', $validated['noreg'])
            ->where('document_type', $validated['document_type'])
            ->first();
        $signatureImages = [];

        foreach ($signatures as $field => $signature) {
            if ($signature === null || $signature === '') {
                continue;
            }

            if (!str_starts_with($signature, 'data:image/')) {
                if ($signature !== $existing?->{$field}) {
                    return new JsonResponse(['message' => 'Path tanda tangan tidak valid'], 422);
                }
                continue;
            }

            $image = $this->decodeSignature($signature);
            if ($image === null) {
                return new JsonResponse(['message' => 'Format tanda tangan tidak valid'], 422);
            }
            $signatureImages[$field] = $image;
        }

        $form = InformConcern::updateOrCreate(
            [
                'noreg' => $validated['noreg'],
                'document_type' => $validated['document_type'],
            ],
            array_merge($validated, ['norm' => $visit->norm])
        );

        foreach ($signatures as $field => $signature) {
            if ($signature === null || $signature === '') {
                $form->{$field} = null;
                continue;
            }

            if (!str_starts_with($signature, 'data:image/')) {
                continue;
            }

            $savedPath = $this->saveSignature($signatureImages[$field], $form, $field);
            if (!$savedPath) {
                return new JsonResponse(['message' => 'Tanda tangan gagal disimpan'], 500);
            }
            $form->{$field} = $savedPath;
        }

        $form->save();

        return new JsonResponse([
            'message' => 'Form berhasil disimpan',
            'data' => $form,
        ]);
    }

    private function decodeSignature(string $signature): ?array
    {
        if (!preg_match('/^data:image\/(png|jpeg);base64,([A-Za-z0-9+\/=]+)$/', $signature, $matches)) {
            return null;
        }

        $image = base64_decode($matches[2], true);
        if ($image === false) {
            return null;
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : 'png';
        return ['image' => $image, 'extension' => $extension];
    }

    private function saveSignature(array $signature, InformConcern $form, string $field): ?string
    {
        $noreg = str_replace('/', '-', $form->noreg);
        $path = 'inform_concern/' . $noreg . '_homecare/' . $form->id . '_' . $field . '.' . $signature['extension'];

        return Storage::disk('remote')->put('public/' . $path, $signature['image']) ? $path : null;
    }
}
