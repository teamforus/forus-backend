<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class AddCriteriaEditableAfterStartToFundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('funds', static function (Blueprint $table) {
            $table->boolean('criteria_editable_after_start')->after('public')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('funds', static function (Blueprint $table) {
            $table->dropColumn('criteria_editable_after_start');
        });
    }
}
