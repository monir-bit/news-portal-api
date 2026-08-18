<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReporterStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:reporters,phone'],
            'designation' => ['required', 'string', 'max:255'],
            'alternate_designation' => ['nullable', 'string', 'max:255'],
            'joining_date' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'max:2048'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'category_id' => ['required', 'exists:categories,id', function ($attr, $val, $fail) {
                $category = \App\Models\Category::find($val);
                if ($category && $category->parent_id !== null) {
                    $fail('Only parent categories can be selected.');
                }
            }],
            'is_active' => ['boolean'],
            'has_location' => ['boolean'],
            'locations' => ['nullable', 'array'],
            'locations.*.division_id' => ['nullable', 'exists:divisions,id'],
            'locations.*.district_id' => ['nullable', 'exists:districts,id'],
            'locations.*.upazila_id' => ['nullable', 'exists:upazilas,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (!$this->boolean('has_location')) {
                return;
            }
            $locs = $this->input('locations', []);
            $count = collect($locs)->filter(fn ($row) => !empty($row['division_id']))->count();
            if ($count < 1) {
                $v->errors()->add('locations', 'When location is enabled, add at least one division.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'This phone number is already registered.',
            'category_id.required' => 'Please select a category.',
        ];
    }
}
