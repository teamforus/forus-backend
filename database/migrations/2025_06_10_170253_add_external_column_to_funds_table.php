<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('funds', function (Blueprint $table) {
            $table->boolean('external')->default(false)->after('type');
        });

        DB::table('funds')
            ->where('type', '=', 'external')
            ->update([ 'external' => true ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('funds')
            ->where('external', '=', true)
            ->update([ 'type' => 'external' ]);

        Schema::table('funds', function (Blueprint $table) {
            $table->dropColumn('external');
        });
    }
};
