<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('funds', function (Blueprint $table) {
            $table->text('how_it_works')->nullable()->after('description_position');
            $table->text('how_it_works_text')->nullable()->after('how_it_works');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('funds', function (Blueprint $table) {
            $table->dropColumn('how_it_works', 'how_it_works_text');
        });
    }
};
