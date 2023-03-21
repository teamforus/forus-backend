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
        Schema::create('banks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key', 200);
            $table->string('name', 200);
            $table->string('oauth_redirect_id')->nullable();
            $table->string('oauth_redirect_url', 2000)->nullable();
            $table->json('data');
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
        Schema::dropIfExists('banks');
    }
};
