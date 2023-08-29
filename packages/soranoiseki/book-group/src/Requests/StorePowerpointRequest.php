<?php

namespace Soranoiseki\BookGroup\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePowerpointRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'pray' => 'required',
            'preach' => 'required',
            'report' => 'required',
            'scripture' => 'required',
            'song' => 'required',
            'worker' => 'required',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => '请选择日期',
            'pray.required' => '请填写代祷事项',
            'preach.required' => '请填写讲道大纲',
            'report.required' => '请填写报告事项',
            'scripture.required' => '请填写经文',
            'song.required' => '请填写诗歌',
            'worker.required' => '请填写服事人员',
        ];
    }
}
