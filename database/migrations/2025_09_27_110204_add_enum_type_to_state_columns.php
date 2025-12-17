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
        Schema::table('bank_connections', function (Blueprint $table) {
            $table
                ->enum('state', [
                    'active', 'expired', 'pending', 'replaced', 'rejected', 'disabled', 'invalid', 'error',
                ])
                ->default('pending')
                ->change();
        });

        Schema::table('identity_2fa_codes', function (Blueprint $table) {
            $table
                ->enum('state', ['active', 'deactivated'])
                ->default('active')
                ->change();
        });

        Schema::table('identity_proxies', function (Blueprint $table) {
            $table
                ->enum('state', ['active', 'pending', 'destroyed', 'terminated', 'expired'])
                ->default('pending')
                ->change();
        });

        Schema::table('prevalidations', function (Blueprint $table) {
            $table
                ->enum('state', ['pending', 'used'])
                ->default('pending')
                ->change();
        });

        Schema::table('record_validations', function (Blueprint $table) {
            $table
                ->enum('state', ['pending', 'approved', 'declined'])
                ->default('pending')
                ->change();
        });

        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table
                ->enum('state', ['pending', 'success', 'canceled'])
                ->default('pending')
                ->change();
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table
                ->enum('state', ['active', 'pending', 'deactivated'])
                ->default('active')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            $table->string('state', 50)->change();
        });

        Schema::table('identity_2fa_codes', function (Blueprint $table) {
            $table->string('state', 20)->default('active')->change();
        });

        Schema::table('identity_proxies', function (Blueprint $table) {
            $table->string('state', 20)->change();
        });

        Schema::table('prevalidations', function (Blueprint $table) {
            $table->string('state')->default('pending')->change();
        });

        Schema::table('record_validations', function (Blueprint $table) {
            $table->string('state', 20)->change();
        });

        Schema::table('voucher_transactions', function (Blueprint $table) {
            $table->string('state')->default('pending')->change();
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('state', 200)->default('active')->change();
        });
    }
};
