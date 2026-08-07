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
        Schema::table('prevalidation_requests', function (Blueprint $table) {
            $table->boolean('missing_records_approved')->default(false)->after('state');
            $table->enum('state', ['pending', 'success', 'fail', 'missing_records'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prevalidation_requests', function (Blueprint $table) {
            $table->dropColumn('missing_records_approved');
            $table->enum('state', ['pending', 'success', 'fail'])->default('pending')->change();
        });
    }
};
