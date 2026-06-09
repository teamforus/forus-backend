<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('openid_flows', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('key', 100);
            $table->string('name', 100);
            $table->json('context')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['provider', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('openid_flows');
    }
};
