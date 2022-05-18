<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class AddHashFieldsToPrevalidationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('prevalidations', static function (Blueprint $table) {
            $table->string('uid_hash', 64)->after('state')->nullable();
            $table->string('records_hash', 64)->after('uid_hash')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('prevalidations', static function (Blueprint $table) {
            $table->dropColumn('uid_hash');
            $table->dropColumn('records_hash');
        });
    }
}
