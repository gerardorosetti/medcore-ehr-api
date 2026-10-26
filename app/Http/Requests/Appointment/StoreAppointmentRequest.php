<?php

declare(strict_types=1);

namespace App\Http\Requests\Appointment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreAppointmentRequest
 *
 * Validates clinical appointment booking payloads.
 */
class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'doctor_profile_id' => ['required', 'uuid', 'exists:doctor_profiles,id'],
            'patient_profile_id' => ['sometimes', 'nullable', 'uuid', 'exists:patient_profiles,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
