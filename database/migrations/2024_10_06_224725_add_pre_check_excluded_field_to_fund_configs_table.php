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
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->boolean('pre_check_excluded')->default(false)->after('provider_products_required');
            $table->string('pre_check_note', 2000)->nullable()->after('pre_check_excluded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('pre_check_excluded');
            $table->dropColumn('pre_check_note');
        });
    }
};
