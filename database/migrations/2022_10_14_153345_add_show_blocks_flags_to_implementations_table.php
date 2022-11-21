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
        Schema::table('implementations', function (Blueprint $table) {
            $table->boolean('show_home_map')->default(true)->after('email_signature');
            $table->boolean('show_home_products')->default(true)->after('show_home_map');
            $table->boolean('show_providers_map')->default(true)->after('show_home_products');
            $table->boolean('show_provider_map')->default(true)->after('show_providers_map');
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
            $table->dropColumn([
                'show_home_map', 'show_home_products',
                'show_providers_map', 'show_provider_map',
            ]);
        });
    }
};
