<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class EmployeeSaveSortRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_ids' => ['required', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'departments' => ['required', 'array'],
            'departments.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ids = $this->input('employee_ids', []);
            if (count($ids) !== count(array_unique($ids))) {
                $validator->errors()->add('employee_ids', 'Duplicate employee ids in sort list.');
            }
            if (Employee::count() !== count($ids)) {
                $validator->errors()->add('employee_ids', 'The sort list must include every employee.');
            }

            $depts = $this->input('departments', []);
            if (Employee::exists() && count($depts) === 0) {
                $validator->errors()->add('departments', 'Department order cannot be empty while employees exist.');
            }
        });
    }
}
