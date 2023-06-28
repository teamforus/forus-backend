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
        Schema::table('organizations', function (Blueprint $table) {
            $table->enum('auth_2fa_policy', ['optional', 'required'])
                ->default('optional')
                ->after('identity_address');

            $table->boolean('auth_2fa_remember_ip')
                ->default(true)
                ->after('auth_2fa_policy');
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
            $table->dropColumn('auth_2fa_policy');
            $table->dropColumn('auth_2fa_remember_ip');
        });
    }
};
