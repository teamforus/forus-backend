<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\RolesTableSeeder;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('role_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 60);
            $table->string('description', 200);
            $table->unsignedInteger('role_id');
            $table->string('locale', 3);

            $table->unique(['role_id', 'locale']);
            $table->foreign('role_id'
            )->references('id')->on('roles')->onDelete('cascade');
        });

        (new RolesTableSeeder())->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('role_translations');
    }
};
