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
        Schema::table('fund_providers', function(Blueprint $table) {
            $table->dropColumn('dismissed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_providers', function(Blueprint $table) {
            $table->boolean('dismissed')->default(false)->after('allow_some_products');
        });
    }
};
