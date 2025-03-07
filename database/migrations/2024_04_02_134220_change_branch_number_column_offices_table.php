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
        Schema::table('offices', function (Blueprint $table) {
            $table->string('branch_number', 12)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('offices', function (Blueprint $table) {
            $table->decimal('branch_number', 12, 0)->nullable()->change();
        });
    }
};
