<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Enums\BloodType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles validation for new patient registration and initial profile creation.
 */
class RegisterPatientRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'max:30'],
            'blood_type' => ['nullable', 'string', Rule::in(BloodType::values())],
            'phone' => ['nullable', 'string', 'max:30'],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['string', 'max:100'],
            'chronic_conditions' => ['nullable', 'string', 'max:1000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
