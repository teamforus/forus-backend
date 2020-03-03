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
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmployeeRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmployeeRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmployeeRole query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmployeeRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmployeeRole whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmployeeRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmployeeRole whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EmployeeRole whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class EmployeeRole extends Model
{
    protected $fillable = [
        'employee_id', 'role_id'
    ];
}
