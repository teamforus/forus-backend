<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class AddOverlayFieldsToImplementationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->boolean('overlay_enabled')->default(false)->after('description');
            $table->string('overlay_type')->default('color')->after('overlay_enabled');
            $table->integer('overlay_opacity')->default(40)->after('overlay_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropColumn([
                'overlay_enabled', 'overlay_type', 'overlay_opacity',
            ]);
        });
    }
}
