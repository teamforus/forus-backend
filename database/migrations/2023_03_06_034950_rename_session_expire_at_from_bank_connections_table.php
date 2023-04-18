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
        Schema::table('bank_connections', function (Blueprint $table) {
            $table->renameColumn('session_expire_at', 'expire_at');
        });

        Schema::table('bank_connections', function (Blueprint $table) {
            $table->date('expire_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            $table->dateTime('expire_at')->nullable()->change();
        });

        Schema::table('bank_connections', function (Blueprint $table) {
            $table->renameColumn('expire_at', 'session_expire_at');
        });
    }
};
