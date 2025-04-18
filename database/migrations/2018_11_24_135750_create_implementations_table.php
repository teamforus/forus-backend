<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('implementations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key', 40);
            $table->string('name', 40);
            $table->string('url_webshop', 200);
            $table->string('url_sponsor', 200);
            $table->string('url_provider', 200);
            $table->string('url_validator', 200);
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
        Schema::dropIfExists('implementations');
    }
};
