<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();

            if (! $user || $user->hasRole('super_admin')) {
                return;
            }

            $requested = $this->input('permissions', []);
            $allowed = $user->getAllPermissions()->pluck('name')->all();
            $escalation = array_values(array_diff($requested, $allowed));

            if ($escalation !== []) {
                $validator->errors()->add(
                    'permissions',
                    'You cannot grant permissions you do not already hold.'
                );
            }
        });
    }
}
