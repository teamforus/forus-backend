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
        Schema::table('voucher_transaction_notes', function (Blueprint $table) {
            $table->boolean('shared')->default(false)->after('group');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('voucher_transaction_notes', function (Blueprint $table) {
            $table->dropColumn('shared');
        });
    }
};
