<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * @noinspection PhpUnused
 */
class PrevalidationsAddExportedFlag extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('prevalidations', function(Blueprint $table) {
            $table->boolean('exported')->default(false)->after('state');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('prevalidations', function(Blueprint $table) {
            $table->dropColumn('exported');
        });
    }
}
