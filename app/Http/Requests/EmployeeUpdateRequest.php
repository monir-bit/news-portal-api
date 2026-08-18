<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $idNo = $this->input('id_no');
        if ($idNo === '' || $idNo === null) {
            $this->merge(['id_no' => null]);
        } elseif (is_string($idNo)) {
            $trimmed = trim($idNo);
            $this->merge(['id_no' => $trimmed === '' ? null : $trimmed]);
        } elseif (is_numeric($idNo)) {
            $this->merge(['id_no' => (string) $idNo]);
        }
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'id_no' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'id_no')->ignore($id)],
            'full_name' => ['required', 'string', 'max:255'],
            'nick_name' => ['nullable', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'mobile_no' => ['required', 'string', 'max:30', Rule::unique('employees', 'mobile_no')->ignore($id)],
            'desk_no' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:255'],
            'beat' => ['nullable', 'string', 'max:255'],
            'blood_group' => ['nullable', 'string', 'max:20'],
            'joining_date' => ['nullable', 'date'],
            'present_address' => ['nullable', 'string'],
            'permanent_address' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
