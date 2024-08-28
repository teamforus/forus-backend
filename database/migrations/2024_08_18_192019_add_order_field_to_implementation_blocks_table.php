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
        Schema::table('implementation_blocks', function (Blueprint $table) {
            $table->integer('order')->after('button_target_blank');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('implementation_blocks', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
