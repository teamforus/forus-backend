<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('physical_cards', function (Blueprint $table) {
            $table->unsignedBigInteger('physical_card_type_id')->nullable()->after('id');

            $table->foreign('physical_card_type_id')
                ->references('id')
                ->on('physical_card_types')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('physical_cards', function (Blueprint $table) {
            $table->dropForeign('physical_cards_physical_card_type_id_foreign');
            $table->dropColumn('physical_card_type_id');
        });
    }
};
