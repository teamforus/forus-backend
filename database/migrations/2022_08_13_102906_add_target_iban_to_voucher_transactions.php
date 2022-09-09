<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('voucher_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('voucher_transactions', 'target')) {
                $table->enum('target', ['identity', 'provider'])
                    ->default('provider')
                    ->after('initiator');
            }

            if (!Schema::hasColumn('voucher_transactions', 'target_iban')) {
                $table->string('target_iban', 200)->nullable()->after('target');
            }

            if (!Schema::hasColumn('voucher_transactions', 'target_name')) {
                $table->string('target_name', 200)->nullable()->after('target_iban');
            }

            if (!Schema::hasColumn('voucher_transactions', 'iban_to_name')) {
                $table->string('iban_to_name', 200)->nullable()->after('iban_to');
            }

            $table->unsignedInteger('organization_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
