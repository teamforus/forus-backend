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
        Schema::table('prevalidations', function (Blueprint $table) {
             $table->index(['fund_id']);
             $table->index(['organization_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('prevalidations', function (Blueprint $table) {
            $table->dropIndex(['fund_id']);
            $table->dropIndex(['organization_id']);
        });
    }
};
