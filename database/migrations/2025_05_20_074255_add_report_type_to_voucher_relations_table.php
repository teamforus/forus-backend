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
        Schema::table('voucher_relations', function (Blueprint $table) {
            $table->enum('report_type', ['user', 'relation'])->default('user')->after('bsn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voucher_relations', function (Blueprint $table) {
            $table->dropColumn('report_type');
        });
    }
};
