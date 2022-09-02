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
        Schema::table('implementations', function(Blueprint $table) {
            $table->string('email_from_address', 50)->nullable()->after('lat');
            $table->string('email_from_name', 50)->nullable()->after('email_from_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('implementations', function(Blueprint $table) {
            $table->dropColumn('email_from_address');
            $table->dropColumn('email_from_name');
        });
    }
};
