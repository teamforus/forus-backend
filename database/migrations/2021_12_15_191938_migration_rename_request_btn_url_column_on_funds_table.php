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
        Schema::table('funds', function (Blueprint $table) {
            $table->renameColumn('request_btn_url', 'external_link_url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('funds', function (Blueprint $table) {
            $table->renameColumn('external_link_url', 'request_btn_url');
        });
    }
};
