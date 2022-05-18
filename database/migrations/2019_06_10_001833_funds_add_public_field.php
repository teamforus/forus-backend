<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * @noinspection PhpUnused
 */
class FundsAddPublicField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('funds', function (Blueprint $table) {
            $table->boolean('public')->default(0)->after('state');
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
            $table->dropColumn('public');
        });
    }
}
