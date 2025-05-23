<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_criteria', function (Blueprint $table) {
            $table->boolean('show_attachment')->default(true)->after('value');
            $table->text('description')->after('show_attachment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_criteria', function (Blueprint $table) {
            $table->dropColumn('show_attachment');
            $table->dropColumn('description');
        });
    }
};
