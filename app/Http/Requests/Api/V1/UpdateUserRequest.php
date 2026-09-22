<?php

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var User|null $user */
        $user = $this->route('user');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'password' => [
                'sometimes',
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
                'sometimes',
                'integer',
                'exists:branches,id',
            ],

            'role_id' => [
                'sometimes',
                'integer',
                'exists:roles,id',
            ],
        ];
    }
}