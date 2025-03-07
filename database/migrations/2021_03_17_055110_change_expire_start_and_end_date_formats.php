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
        Schema::table('products', function (Blueprint $table) {
            $table->date('expire_at')->nullable()->default(null)->change();
        });

        Schema::table('funds', function (Blueprint $table) {
            $table->date('start_date')->nullable()->default(null)->change();
            $table->date('end_date')->nullable()->default(null)->change();
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->date('expire_at')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
    }
};
