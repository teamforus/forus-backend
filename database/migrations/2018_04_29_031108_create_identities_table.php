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
        Schema::create('identities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('pin_code');
            $table->string('public_key', 200);
            $table->string('private_key', 200);
            $table->string('passphrase', 200)->nullable();
            $table->string('address', 200)->unique();
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
        Schema::dropIfExists('identities');
    }
};
