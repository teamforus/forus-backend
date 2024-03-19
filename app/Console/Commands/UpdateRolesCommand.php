<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Role;
use Database\Seeders\PermissionsTableSeeder;
use Database\Seeders\RolePermissionsTableSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class UpdateRolesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seeders:update-roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update roles.';
}
