<?php

namespace Soranoiseki\BookGroup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskPlanRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => 'string|required',
            'members' => 'string|nullable',
            'date' => 'string|required',
        ];
    }

    public function messages(): array
    {
        return [
            'role.required' => 'Role is required',
            'date.required' => 'Date is required',
        ];
    }

    public function attributes(): array
    {
        return [
            'role' => 'Group Role',
            'date' => 'Date',
        ];
    }
}
