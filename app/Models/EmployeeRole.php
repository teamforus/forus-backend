<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class EmployeeRole
 * @property mixed $id
 * @property string $employee_id
 * @property string $role_id
 * @package App\Models
 */
class EmployeeRole extends Model
{
    protected $fillable = [
        'employee_id', 'role_id'
    ];
}
