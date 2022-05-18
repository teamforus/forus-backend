<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * @noinspection PhpUnused
 */
class CreateEmployeeRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('employee_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('employee_id')->unsigned()->index();
            $table->integer('role_id')->unsigned()->index();
            $table->timestamps();

            $table->foreign('employee_id'
            )->references('id')->on('employees')->onDelete('cascade');

            $table->foreign('role_id'
            )->references('id')->on('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_roles');
    }
}
