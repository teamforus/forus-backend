<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->decimal('notification_amount', 10, 2)->nullable()->after('state');

            DB::statement('ALTER TABLE funds MODIFY start_date TIMESTAMP NULL');
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
            $table->dropColumn('notification_amount');

            DB::statement('ALTER TABLE funds MODIFY start_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        });
    }
};
