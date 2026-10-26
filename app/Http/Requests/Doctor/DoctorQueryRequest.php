<?php

declare(strict_types=1);

namespace App\Http\Requests\Doctor;

use App\Models\DoctorProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles query parameter validation for doctor profile search, filtering, and pagination.
 */
class DoctorQueryRequest extends FormRequest
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
            'specialty' => ['sometimes', 'string', 'max:100'],
            'accepting_patients' => ['sometimes', 'boolean'],
            'sort_by' => ['sometimes', 'string', Rule::in(DoctorProfile::ALLOWED_SORT_COLUMNS)],
            'direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
