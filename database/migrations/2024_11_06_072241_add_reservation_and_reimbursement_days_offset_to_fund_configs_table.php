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
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->integer('reservation_approve_offset')
                ->default(0)
                ->after('csv_primary_key');

            $table->integer('reimbursement_approve_offset')
                ->default(0)
                ->after('reservation_approve_offset');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('reservation_approve_offset');
            $table->dropColumn('reimbursement_approve_offset');
        });
    }
};
