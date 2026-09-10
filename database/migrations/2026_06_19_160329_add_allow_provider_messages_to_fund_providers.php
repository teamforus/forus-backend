<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->boolean('allow_provider_messages')->default(false)->after('allow_extra_payments_full');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_providers', function (Blueprint $table) {
            $table->dropColumn('allow_provider_messages');
        });
    }
};
