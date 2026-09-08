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
        Schema::table('fund_request_clarifications', function (Blueprint $table) {
            $table->enum('state', [
                'pending', 'answered', 'closed',
            ])->default('pending')->change();

            $table->renameColumn('answered_at', 'resolved_at');
            $table->timestamp('changed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_request_clarifications', function (Blueprint $table) {
            $table->renameColumn('resolved_at', 'answered_at');
            $table->dropColumn('changed_at');
        });
    }
};
