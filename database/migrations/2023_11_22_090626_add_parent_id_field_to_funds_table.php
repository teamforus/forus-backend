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
        Schema::table('funds', function (Blueprint $table) {
            $table->unsignedInteger('parent_id')->nullable()->after('description_position');

            $table->foreign('parent_id')
                ->references('id')
                ->on('funds')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('funds', function (Blueprint $table) {
            $table->dropForeign('funds_parent_id_foreign');
            $table->dropColumn('parent_id');
        });
    }
};
