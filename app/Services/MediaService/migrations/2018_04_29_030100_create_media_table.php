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
        Schema::create('media', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uid', 200)->nullable();
            $table->string('original_name', 200)->nullable();

            $table->string('type',20);
            $table->string('ext', 10);
            $table->unsignedInteger('order')->default(0);

            $table->string('identity_address', 200)->default('');

            $table->integer('mediable_id')->nullable()->unsigned();
            $table->string('mediable_type', 80)->nullable();
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
        Schema::dropIfExists('media');
    }
};
