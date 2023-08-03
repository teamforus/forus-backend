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
        DB::statement("ALTER TABLE fund_configs MODIFY COLUMN auth_2fa_policy ENUM('global', 'optional', 'required', 'restrict_features') DEFAULT 'global'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {

        DB::statement("ALTER TABLE fund_configs MODIFY COLUMN auth_2fa_policy ENUM('optional', 'required', 'restrict_features') DEFAULT 'optional'");
    }
};
