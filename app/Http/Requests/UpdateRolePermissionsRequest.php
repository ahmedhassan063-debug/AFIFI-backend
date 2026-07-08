<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermissionsRequest extends FormRequest
{
    /**
     * Authorization is handled by RolePolicy::update() via the controller's
     * $this->authorize() call, which also blocks editing super_admin.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'present' (not 'required') so an empty array is a valid,
            // explicit "remove all permissions from this role" request.
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }
}
