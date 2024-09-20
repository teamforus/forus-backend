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
            $table->enum(
                'shown_criteria_label_details',
                ['optional', 'required', 'both']
            )->after('provider_products_required')->default('both');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn('shown_criteria_label_details');
        });
    }
};
