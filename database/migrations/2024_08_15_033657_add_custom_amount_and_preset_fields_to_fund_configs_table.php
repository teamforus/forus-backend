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
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->enum('outcome_type', ['voucher', 'payout'])->default('voucher')->after('key');
            $table->boolean('allow_custom_amounts')->default(false)->after('allow_voucher_records');
            $table->boolean('allow_custom_amounts_validator')->default(false)->after('allow_custom_amounts');
            $table->boolean('allow_preset_amounts')->default(false)->after('allow_custom_amounts_validator');
            $table->boolean('allow_preset_amounts_validator')->default(false)->after('allow_preset_amounts');
            $table->decimal('custom_amount_min')->default(null)->nullable()->after('allow_preset_amounts_validator');
            $table->decimal('custom_amount_max')->default(null)->nullable()->after('custom_amount_min');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn([
                'outcome_type', 'allow_custom_amounts', 'allow_custom_amounts_validator',
                'allow_preset_amounts', 'allow_preset_amounts_validator',
                'custom_amount_min', 'custom_amount_max',
            ]);
        });
    }
};
