<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->enum('initiator', ['provider', 'sponsor', 'requester'])
                ->default('provider')
                ->after('state')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('voucher_transactions')
            ->where('initiator', 'requester')
            ->update(['initiator' => 'provider']);

        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->enum('initiator', ['provider', 'sponsor'])
                ->default('provider')
                ->after('state')
                ->change();
        });
    }
};
