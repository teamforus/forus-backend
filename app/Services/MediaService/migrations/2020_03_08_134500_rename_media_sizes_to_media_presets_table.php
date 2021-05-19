<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Class RenameMediaSizesToMediaPresetsTable
 * @noinspection PhpIllegalPsrClassPathInspection
 * @noinspection PhpUnused
 */
class RenameMediaSizesToMediaPresetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::rename('media_sizes', 'media_presets');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::rename('media_presets', 'media_sizes');
    }
}
