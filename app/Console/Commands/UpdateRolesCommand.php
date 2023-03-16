<?php

namespace App\Console\Commands;

use App\Models\Role;
use Database\Seeders\PermissionsTableSeeder;
use Database\Seeders\RolePermissionsTableSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Console\Command;

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

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $seeder = new RolesTableSeeder();

        $invalidRoles = Role::whereHas('employees')
            ->whereNotIn('key', array_keys($seeder->getRoles()))
            ->exists();

        if ($invalidRoles) {
            $this->warn('There are employees with roles attached, not existing in seeder file');
            return;
        }

        $seeder->run();

        $this->info('Roles updated!');
        $this->info('Updating permissions');

        (new PermissionsTableSeeder())->run();
        (new RolePermissionsTableSeeder())->run();

        $this->info('Roles and permissions updated!');
    }
}
