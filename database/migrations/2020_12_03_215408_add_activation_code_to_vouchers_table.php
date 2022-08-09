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
        Schema::table('vouchers', static function (Blueprint $table) {
            $table->string('activation_code')->nullable()->after('employee_id');
            $table->enum('state', ['pending', 'active'])->default('active')->after('activation_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('vouchers', static function (Blueprint $table) {
            $table->dropColumn('activation_code');
            $table->dropColumn('state');
        });
    }
};
