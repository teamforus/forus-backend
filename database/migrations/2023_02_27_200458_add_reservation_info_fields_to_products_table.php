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
        Schema::table('products', function (Blueprint $table) {
            $options = ['global', 'no', 'optional', 'required'];

            $table->enum('reservation_phone', $options)->default('global')->after('reservation_policy');
            $table->enum('reservation_address', $options)->default('global')->after('reservation_phone');
            $table->enum('reservation_birth_date', $options)->default('global')->after('reservation_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('reservation_phone');
            $table->dropColumn('reservation_address');
            $table->dropColumn('reservation_birth_date');
        });
    }
};
