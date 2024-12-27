<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookRequest extends FormRequest
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
            'title' => 'required|max:255',
            'summary' => 'required',
            'stock' => 'required',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:2048',
            'category_id' => 'required|exists:categories,id'
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'inputan title tidak boleh kosong',
            'summary.required' => 'inputan summary tidak boleh kosong',
            'stock.required' => 'inputan stok tidak boleh kosong',
            'image.mimes' => 'format image hanya boleh jpg, jpeg, png',
            'category_id.required' => 'category_id tidak boleh kosong',
            'category_id.exist' => 'id category tidak ditemukan di data genre',
            'title.max' => 'inputan title maksimal 255',
        ];
    }
}
