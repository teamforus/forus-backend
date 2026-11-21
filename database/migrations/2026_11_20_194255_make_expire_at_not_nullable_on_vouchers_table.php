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
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->date('expire_at')->nullable(false)->change();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table): void {
            $table->date('expire_at')->nullable()->change();
        });
    }
};
