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
