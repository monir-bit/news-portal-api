<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReporterUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reporterId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:reporters,phone,' . $reporterId],
            'designation' => ['required', 'string', 'max:255'],
            'alternate_designation' => ['nullable', 'string', 'max:255'],
            'joining_date' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'max:2048'],
            'category_id' => ['required', 'exists:categories,id', function ($attr, $val, $fail) {
                $category = \App\Models\Category::find($val);
                if ($category && $category->parent_id !== null) {
                    $fail('Only parent categories can be selected.');
                }
            }],
            'is_active' => ['boolean'],
            'has_location' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'This phone number is already registered.',
            'category_id.required' => 'Please select a category.',
        ];
    }
}
