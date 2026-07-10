<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use AuthorizesRequests;

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();

        return RoleResource::collection($roles);
    }

    public function show(Role $role): RoleResource
    {
        $this->authorize('view', $role);

        return new RoleResource(
            $role->loadCount(['permissions', 'users'])->load(['permissions'])
        );
    }

    /**
     * Full list of system permissions, used to render the permission
     * checkboxes when editing a role.
     */
    public function permissions(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Role::class);

        return PermissionResource::collection(Permission::query()->orderBy('name')->get());
    }

    public function updatePermissions(UpdateRolePermissionsRequest $request, Role $role): RoleResource
    {
        $this->authorize('update', $role);

        $role->syncPermissions($request->validated('permissions'));

        return new RoleResource(
            $role->loadCount(['permissions', 'users'])->load(['permissions'])
        );
    }
}
