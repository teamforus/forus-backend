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
        DB::statement("ALTER TABLE organizations MODIFY COLUMN auth_2fa_policy ENUM('optional', 'required') DEFAULT 'optional' AFTER bank_cron_time");
        DB::statement("ALTER TABLE organizations MODIFY COLUMN auth_2fa_remember_ip BOOLEAN AFTER auth_2fa_policy");

        Schema::table('organizations', function (Blueprint $table) {
            $table->enum('auth_2fa_funds_policy', [
                'optional', 'required', 'restrict_features'
            ])->default('optional')->after('auth_2fa_remember_ip');

            $table->boolean('auth_2fa_funds_remember_ip')
                ->default(true)
                ->after('auth_2fa_funds_policy');

            $table->boolean('auth_2fa_funds_restrict_emails')
                ->default(false)
                ->after('auth_2fa_funds_remember_ip');

            $table->boolean('auth_2fa_funds_restrict_auth_sessions')
                ->default(false)
                ->after('auth_2fa_funds_restrict_emails');

            $table->boolean('auth_2fa_funds_restrict_reimbursements')
                ->default(false)
                ->after('auth_2fa_funds_restrict_auth_sessions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('auth_2fa_funds_policy');
            $table->dropColumn('auth_2fa_funds_remember_ip');
            $table->dropColumn('auth_2fa_funds_restrict_emails');
            $table->dropColumn('auth_2fa_funds_restrict_auth_sessions');
            $table->dropColumn('auth_2fa_funds_restrict_reimbursements');
        });
    }
};
