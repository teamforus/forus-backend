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
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->enum('state', ['pending', 'approved', 'declined', 'approved_partly', 'disregarded'])
                ->default('pending')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->enum('state', ['pending', 'approved', 'declined', 'approved_partly'])
                ->default('pending')
                ->change();
        });
    }
};
