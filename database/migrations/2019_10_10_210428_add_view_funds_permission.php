<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

class AddViewFundsPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Permission::where('key', 'view_funds')->exists()) {
            $permission = Permission::create([
                'key' => 'view_funds',
                'name' => 'See funds overview',
            ]);

            /** @var Role $role */
            $role = Role::where('key', 'validation')->get();

            $role->role_permissions()->firstOrCreate([
                'permission_id' => $permission->id
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
