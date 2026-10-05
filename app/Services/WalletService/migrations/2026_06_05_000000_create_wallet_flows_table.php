<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     * @return void
     */
    public function up(): void
    {
        Schema::create('wallet_flows', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->enum('type', ['authentication', 'disclosure'])->default('authentication');
            $table->string('key', 100);
            $table->string('name', 100);
            $table->json('context')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['provider', 'type', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down(): void
    {
        Schema::drop('wallet_flows');
    }
};
