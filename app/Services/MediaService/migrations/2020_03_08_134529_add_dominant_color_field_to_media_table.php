<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AddDominantColorFieldToMediaTable
 * @noinspection PhpIllegalPsrClassPathInspection
 * @noinspection PhpUnused
 */
class AddDominantColorFieldToMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('dominant_color', 200)->default('')->after('ext');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('dominant_color');
        });
    }
}
