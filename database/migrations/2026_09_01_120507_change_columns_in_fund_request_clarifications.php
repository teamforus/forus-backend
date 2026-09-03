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
        Schema::table('fund_request_clarifications', function (Blueprint $table) {
            $table->enum('state', [
                'pending', 'answered', 'closed',
            ])->default('pending')->change();

            $table->renameColumn('answered_at', 'resolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_request_clarifications', function (Blueprint $table) {
            $table->enum('state', [
                'pending', 'answered',
            ])->default('pending')->change();

            $table->renameColumn('resolved_at', 'answered_at');
        });
    }
};
