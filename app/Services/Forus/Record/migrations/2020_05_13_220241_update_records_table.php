<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 * @noinspection PhpIllegalPsrClassPathInspection
 */
class UpdateRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->softDeletes()->after('order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
