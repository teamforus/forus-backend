<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('identity_proxies', function (Blueprint $table): void {
            $table->string('access_token', 200)->nullable(false)->change();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('identity_proxies', function (Blueprint $table): void {
            $table->string('access_token', 200)->nullable()->change();
        });
    }
};
