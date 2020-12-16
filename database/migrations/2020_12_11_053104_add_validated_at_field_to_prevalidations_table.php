<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Prevalidation;
use Illuminate\Support\Facades\DB;

/**
 * Class AddValidatedAtFieldToPrevalidationsTable
 * @noinspection PhpUnused
 */
class AddValidatedAtFieldToPrevalidationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('prevalidations', static function (Blueprint $table) {
            $table->dateTime('validated_at')->after('exported')->nullable();
        });

        Prevalidation::whereNull('validated_at')->update([
            'validated_at' => DB::raw('`created_at`'),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('prevalidations', static function (Blueprint $table) {
            $table->dropColumn('validated_at');
        });
    }
}
