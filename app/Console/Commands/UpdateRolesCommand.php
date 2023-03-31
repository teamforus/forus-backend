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

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $seeder = new RolesTableSeeder();

        $invalidEmptyRoles = Role::query()
            ->whereDoesntHave('employees', fn (Employee|Builder $b) => $b->withTrashed())
            ->whereNotIn('key', array_keys($seeder->getRoles()))
            ->pluck('key');

        $invalidUsedRoles = Role::query()
            ->whereHas('employees', fn (Employee|Builder $b) => $b->withTrashed())
            ->whereNotIn('key', array_keys($seeder->getRoles()))
            ->pluck('key');

        if ($invalidEmptyRoles->isNotEmpty() || $invalidUsedRoles->isNotEmpty()) {
            $this->warn(implode("\n", array_filter([
                'The system currently uses roles that are not in the seed file.',
                'Please delete or update the following roles manually and try again: ',
                $invalidEmptyRoles->isNotEmpty() ?
                    ' - Roles without employees: ' . $invalidEmptyRoles->join(', ') . '.' : null,
                $invalidUsedRoles->isNotEmpty() ?
                    ' - Roles with employees: ' . $invalidUsedRoles->join(', ') . '.' : null,
            ])));

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
