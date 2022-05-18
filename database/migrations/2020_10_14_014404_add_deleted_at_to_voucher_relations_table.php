<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class AddDeletedAtToVoucherRelationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('voucher_relations', static function (Blueprint $table) {
            $table->softDeletes()->after('bsn');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('voucher_relations', static function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
