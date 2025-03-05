<?php

namespace Soranoiseki\BookGroup\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateMemberRequest extends FormRequest
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
            'name' => 'string|required',
            'role' => 'string|required',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'role.required' => 'Role is required',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'role' => 'Group Role',
        ];
    }
}
