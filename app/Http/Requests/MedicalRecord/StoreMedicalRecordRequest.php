<?php

declare(strict_types=1);

namespace App\Http\Requests\MedicalRecord;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreMedicalRecordRequest
 *
 * Validates clinical consultation payloads including diagnosis, clinical notes,
 * treatment plan, optional structured vital signs, and medication prescription items.
 */
class StoreMedicalRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is delegated to the MedicalRecordPolicy via Gate checks in the controller.
     */
    #[\NoDiscard]
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    #[\NoDiscard]
    public function rules(): array
    {
        return [
            'diagnosis' => ['required', 'string', 'min:5', 'max:2000'],
            'clinical_notes' => ['required', 'string', 'min:10', 'max:5000'],
            'treatment_plan' => ['nullable', 'string', 'max:3000'],
            'vital_signs' => ['nullable', 'array'],
            'vital_signs.blood_pressure' => ['nullable', 'string', 'max:20'],
            'vital_signs.heart_rate_bpm' => ['nullable', 'integer', 'between:20,300'],
            'vital_signs.temperature_c' => ['nullable', 'numeric', 'between:30,45'],
            'vital_signs.spo2_percent' => ['nullable', 'integer', 'between:50,100'],
            'prescriptions' => ['nullable', 'array'],
            'prescriptions.*.medication_name' => ['required_with:prescriptions', 'string', 'max:150'],
            'prescriptions.*.dosage' => ['required_with:prescriptions', 'string', 'max:80'],
            'prescriptions.*.frequency' => ['required_with:prescriptions', 'string', 'max:100'],
            'prescriptions.*.duration_days' => ['required_with:prescriptions', 'integer', 'min:1', 'max:365'],
            'prescriptions.*.instructions' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
