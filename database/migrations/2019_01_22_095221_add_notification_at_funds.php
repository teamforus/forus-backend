<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNotificationAtFunds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('funds', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('notification_amount');
        });

        DB::table('funds')->update([
            'notification_amount' => 10000
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('funds', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });

        DB::table('funds')->update([
            'notification_amount' => null
        ]);
    }
}
