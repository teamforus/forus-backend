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
        Schema::table('offices', function (Blueprint $table) {
            $table->string('branch_name', 100)->nullable()->after('phone');
            $table->string('branch_number', 100)->nullable()->after('branch_name');
            $table->string('branch_id', 100)->nullable()->after('branch_number');
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
            $table->dropColumn('branch_name');
            $table->dropColumn('branch_number');
            $table->dropColumn('branch_id');
        });
    }
};
