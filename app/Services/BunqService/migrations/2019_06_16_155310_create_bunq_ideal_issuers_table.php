<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('bunq_ideal_issuers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 200);
            $table->string('bic', 200);
            $table->boolean('sandbox');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('bunq_ideal_issuers');
    }
};
