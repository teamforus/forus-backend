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
        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->enum('target', ['provider', 'iban', 'top_up', 'payout'])
                ->default('provider')
                ->after('initiator')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
