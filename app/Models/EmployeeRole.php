<?php

namespace App\Models;

/**
 * App\Models\EmployeeRole
 *
 * @property int $id
 * @property int $employee_id
 * @property int $role_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|EmployeeRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmployeeRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EmployeeRole query()
 * @method static \Illuminate\Database\Eloquent\Builder|EmployeeRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmployeeRole whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmployeeRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmployeeRole whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EmployeeRole whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EmployeeRole extends BaseModel
{
    protected $fillable = [
        'employee_id', 'role_id'
    ];
}
