<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('fund_request_records', function (Blueprint $table) {
            $table->enum('source', ['brp', 'form', 'wallet'])->default('form')->change();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_request_records', function (Blueprint $table) {
            $table->enum('source', ['brp', 'form'])->default('form')->change();
        });
    }
};
