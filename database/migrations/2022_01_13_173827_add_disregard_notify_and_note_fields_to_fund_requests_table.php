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
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->string('disregard_note', 2000)->default('')->after('note');
            $table->boolean('disregard_notify')->default(false)->after('disregard_note');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_requests', function (Blueprint $table) {
            $table->dropColumn('disregard_note', 'disregard_notify');
        });
    }
};
