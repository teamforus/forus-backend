<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderFieldToMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (!Schema::hasColumn('media', 'order')) {
            Schema::table('media', static function (Blueprint $table) {
                $table->unsignedInteger('order')->default(0)->after('ext');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('media', static function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
}
