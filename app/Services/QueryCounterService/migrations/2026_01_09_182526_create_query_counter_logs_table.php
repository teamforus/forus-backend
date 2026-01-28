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
        Schema::create('query_counter_logs', function (Blueprint $table) {
            $table->id();
            $table->string('group', 200)->nullable();
            $table->string('route_name')->nullable();
            $table->text('url');
            $table->json('query')->nullable();
            $table->string('locale', 8)->nullable();
            $table->double('sql_queries_time', 12, 2);
            $table->unsignedInteger('sql_queries_count');
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
        Schema::dropIfExists('query_counter_logs');
    }
};
