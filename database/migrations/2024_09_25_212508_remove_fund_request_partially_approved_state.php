<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('fund_requests')->where('state', 'approved_partly')->update([
            'state' => 'declined',
        ]);

        Schema::table('fund_requests', function (Blueprint $table) {
            $table->enum('state', [
                'pending', 'approved', 'declined', 'disregarded',
            ])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->enum('state', [
                'pending', 'approved', 'declined', 'approved_partly', 'disregarded',
            ])->nullable()->default('pending')->change();
        });
    }
};
