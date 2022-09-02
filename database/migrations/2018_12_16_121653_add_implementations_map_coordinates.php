<?php

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
        Schema::table('implementations', function(Blueprint $table) {
            $table->double('lon')->nullable()->after('url_app');
            $table->double('lat')->nullable()->after('lon');
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
            $table->dropColumn('lon');
            $table->dropColumn('lat');
        });
    }
};
