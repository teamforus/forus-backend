<?php

use App\Models\Prevalidation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
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

        Prevalidation::withTrashed()->whereNull('validated_at')->update([
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
};
