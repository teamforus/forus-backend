<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('implementation_blocks', function (Blueprint $table) {
            $table->string('label', 30)->nullable()->change();
            $table->string('title', 100)->nullable()->change();
            $table->string('description', 500)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('implementation_blocks', function (Blueprint $table) {
            $table->string('label', 200)->nullable()->change();
            $table->string('title', 200)->nullable()->change();
            $table->string('description', 5000)->nullable()->change();
        });
    }
};
