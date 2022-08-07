<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductboardApiKeyFieldToImplementationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->string('productboard_api_key', 1000)->nullable()->after('informal_communication');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropColumn('productboard_api_key');
        });
    }
}
