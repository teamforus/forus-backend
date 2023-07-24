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
        Schema::table('identity_2fa_codes', function (Blueprint $table) {
            $table->string('state', 20)->default('active')->after('identity_2fa_uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('identity_2fa_codes', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
};
