<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Role;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesApplication;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use DoesTesting, DatabaseTransactions, CreatesApplication;

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testUserEmails(): void
    {
        $employee = Employee::create([
            'email' => 'test1@test.mail',
            'role'  => Role::first(),
        ]);

        $this->assertCount(1, $employee->roles);
        $this->assertEquals(Role::first(), $employee->role);
        $this->assertEquals('test1@test.mail', $employee->email);

        $role = Role::inRandomOrder()->first();
        $employee->update([
            'email' => 'test2@test.mail',
            'role' => Role::inRandomOrder()->first(),
        ]);
        $this->assertCount(1, $employee->roles);
        $this->assertEquals($role, $employee->role);
        $this->assertEquals('test2@test.mail', $employee->email);

        $employee->delete();
        $this->assertNull('test2@test.mail', $employee->id);
    }
}