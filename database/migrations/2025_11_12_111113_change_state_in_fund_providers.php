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
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->enum('state', ['pending', 'accepted', 'rejected', 'unsubscribed'])
                ->default('pending')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->enum('state', ['pending', 'accepted', 'rejected'])
                ->default('pending')
                ->change();
        });
    }
};
