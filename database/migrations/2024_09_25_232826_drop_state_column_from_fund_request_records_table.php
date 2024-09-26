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
        Schema::table('fund_request_records', function (Blueprint $table) {
            $table->dropColumn('state');
            $table->dropColumn('note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_request_records', function (Blueprint $table) {
            $table->enum('state', [
                'pending', 'approved', 'declined', 'disregarded',
            ])->default('pending');
            $table->string('note', 2000)->default('');
        });
    }
};
