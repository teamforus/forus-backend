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
        Schema::table('funds', function (Blueprint $table) {
            $table->boolean('external_page')->default(false)->after('external_link_text');
            $table->string('external_page_url')->nullable()->after('external_page');
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
            $table->dropColumn(['external_page', 'external_page_url']);
        });
    }
};
