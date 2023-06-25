<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('b_i_connections', function (Blueprint $table) {
            $table->id();
            $table->integer('organization_id')->unsigned();
            $table->string('token', 64);
            $table->enum('auth_type', ['header', 'parameter'])->default('header');
            $table->timestamps();

            $table->foreign('organization_id'
            )->references('id')->on('organizations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('b_i_connections');
    }
};
