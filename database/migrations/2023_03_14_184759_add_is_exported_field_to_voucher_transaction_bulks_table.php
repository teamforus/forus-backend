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
        Schema::table('voucher_transaction_bulks', function (Blueprint $table) {
            $table->boolean('is_exported')->default(false)->after('accepted_manually');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('voucher_transaction_bulks', function (Blueprint $table) {
            $table->dropColumn('is_exported');
        });
    }
};
