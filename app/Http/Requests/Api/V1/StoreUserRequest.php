<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
            ],

            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    'activo',
                    'inactivo',
                ]),
            ],

            'branch_id' => [
                'required',
                'integer',
                'exists:branches,id',
            ],

            'role_id' => [
                'required',
                'integer',
                'exists:roles,id',
            ],
        ];
    }
}