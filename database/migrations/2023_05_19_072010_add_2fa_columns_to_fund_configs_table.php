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
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->enum('auth_2fa_policy', ['optional', 'required', 'restrict_features'])
                ->default('optional')
                ->after('key');

            $table->boolean('auth_2fa_remember_ip')
                ->default(true)
                ->after('auth_2fa_policy');

            $table->boolean('auth_2fa_restrict_emails')
                ->default(false)
                ->after('auth_2fa_remember_ip');

            $table->boolean('auth_2fa_restrict_auth_sessions')
                ->default(false)
                ->after('auth_2fa_restrict_emails');

            $table->boolean('auth_2fa_restrict_reimbursements')
                ->default(false)
                ->after('auth_2fa_restrict_auth_sessions');

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
            $table->dropColumn('auth_2fa_policy');
            $table->dropColumn('auth_2fa_remember_ip');
            $table->dropColumn('auth_2fa_restrict_emails');
            $table->dropColumn('auth_2fa_restrict_auth_sessions');
            $table->dropColumn('auth_2fa_restrict_reimbursements');
        });
    }
};
