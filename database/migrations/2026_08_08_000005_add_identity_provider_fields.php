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
        Schema::table('organizations', function (Blueprint $table) {
            $table->enum('allow_identity_providers', ['no', 'sso'])->default('no')->after('allow_bi_connection');
        });

        Schema::table('implementations', function (Blueprint $table) {
            $table->boolean('entra_login_enabled')->default(false)->after('digid_enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropColumn('entra_login_enabled');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('allow_identity_providers');
        });
    }
};
