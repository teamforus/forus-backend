<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Http\Controllers\Controller;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return RoleResource::queryCollection(Role::query(), Role::count());
    }

    /**
     * Display the specified resource.
     *
     * @param Role $role
     * @return RoleResource
     */
    public function show(Role $role): RoleResource
    {
        return RoleResource::create($role);
    }
}
