<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeRole extends Model
{
    protected $fillable = [
        'employee_id', 'role_id'
    ];

    public function employee() {

    }
}
