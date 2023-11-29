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
        Schema::table('fund_criteria', function (Blueprint $table) {
            $table->boolean('optional')->after('value')->default(false);
            $table->string('min')->after('optional')->nullable();
            $table->string('max')->after('min')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_criteria', function (Blueprint $table) {
            $table->dropColumn('optional', 'min', 'max');
        });
    }
};
