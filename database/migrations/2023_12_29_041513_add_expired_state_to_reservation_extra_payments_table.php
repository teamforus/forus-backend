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
        Schema::table('reservation_extra_payments', function (Blueprint $table) {
            $table->enum('state', ['open', 'paid', 'failed', 'pending', 'canceled', 'expired'])
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
        Schema::table('reservation_extra_payments', function (Blueprint $table) {
            $table->enum('state', ['open', 'paid', 'failed', 'pending', 'canceled'])
                ->default('pending')
                ->change();
        });
    }
};
