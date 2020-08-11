<?php

use App\Models\Prevalidation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

        Prevalidation::with([
            'fund.fund_config', 'prevalidation_records.record_type',
        ])->get()->each(static function(Prevalidation $prevalidation) {
            $prevalidation->updateHashes();
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
