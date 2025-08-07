<?php

namespace Soranoiseki\BookGroup\Http\Requests;

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
            // 'pray.item1' => 'required',
            // 'pray.item2' => 'required',
            // 'pray.item3' => 'required',
            // 'preach.item0' => 'required',
            // 'preach.item1' => 'required',
            // 'preach.item2' => 'required',
            // 'preach.item3' => 'required',
            // 'report' => 'required',
            // 'scripture.item1' => 'required',
            // 'scripture.item2' => 'required',
            // 'scripture.item3' => 'required',
            // 'song.item1' => 'required',
            // 'song.item2' => 'required',
            // 'song.item3' => 'required',
            // 'song.item4' => 'required',
            // 'worker.item1' => 'required',
            // 'worker.item2' => 'required',
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
            'pray.item1.required' => '请填写代祷事项（教会）',
            'pray.item2.required' => '请填写代祷事项（姐妹团契1）',
            'pray.item3.required' => '请填写代祷事项（姐妹团契2）',
            'preach.item0.required' => '请填写讲道标题',
            'preach.item1.required' => '请填写讲道（引言）',
            'preach.item2.required' => '请填写讲道（经文理解与应用）',
            'preach.item3.required' => '请填写讲道（总结）',
            'report.item1.required' => '请填写至少一个报告事项',
            'scripture.item1.required' => '请填写经文（宣召）',
            'scripture.item2.required' => '请填写经文（启应经文）',
            'scripture.item3.required' => '请填写经文（读经）',
            'song.item1.required' => '请填写诗歌1',
            'song.item2.required' => '请填写诗歌2',
            'song.item3.required' => '请填写诗歌3',
            'song.item4.required' => '请填写回应诗歌',
            'worker.item1.required' => '请填写服事人员（本周）',
            'worker.item2.required' => '请填写服事人员（本周）',
        ];
    }
}
