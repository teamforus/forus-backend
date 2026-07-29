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
        Schema::table('fund_backoffice_logs', function (Blueprint $table) {
            $table->string('correlation_id')->nullable()->after('response_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_backoffice_logs', function (Blueprint $table) {
            $table->dropColumn('correlation_id');
        });
    }
};
