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
        Schema::table('identity_proxies', function (Blueprint $table) {
            $table->index('access_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('identity_proxies', function (Blueprint $table) {
            $table->dropIndex('identity_proxies_access_token_index');
        });
    }
};
