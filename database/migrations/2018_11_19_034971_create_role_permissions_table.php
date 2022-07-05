<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Database\Seeders\RolePermissionsTableSeeder;

/**
 * @noinspection PhpUnused
 */
class CreateRolePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('role_id')->unsigned()->index();
            $table->integer('permission_id')->unsigned()->index();

            $table->foreign('role_id'
            )->references('id')->on('roles')->onDelete('cascade');

            $table->foreign('permission_id'
            )->references('id')->on('permissions')->onDelete('cascade');
        });

        (new RolePermissionsTableSeeder())->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
}
