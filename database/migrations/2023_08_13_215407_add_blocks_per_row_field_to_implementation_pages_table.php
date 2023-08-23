<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('implementation_pages', function (Blueprint $table) {
            $table->tinyInteger('blocks_per_row')->after('external')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('implementation_pages', function (Blueprint $table) {
            $table->dropColumn('blocks_per_row');
        });
    }
};
