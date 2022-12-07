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
            $table->boolean('button_link_target_is_blank')->default(false)->after('button_link');
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
            $table->dropColumn('button_link_target_is_blank');
        });
    }
};
