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
        Schema::table('physical_card_requests', function (Blueprint $table) {
            $table->string('card_code', 50)->nullable()->unique()->after('city');
            $table->unsignedInteger('physical_card_id')->nullable()->after('card_code');

            $table->foreign('physical_card_id')
                ->references('id')
                ->on('physical_cards')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('physical_card_requests', function (Blueprint $table) {
            $table->dropForeign(['physical_card_id']);
            $table->dropColumn('card_code', 'physical_card_id');
        });
    }
};
