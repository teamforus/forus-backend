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
        Schema::table('physical_card_types', function (Blueprint $table) {
            $table->boolean('send_to_dcp')->default(false)->after('code_block_size');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('physical_card_types', function (Blueprint $table) {
            $table->dropColumn('send_to_dcp');
        });
    }
};
