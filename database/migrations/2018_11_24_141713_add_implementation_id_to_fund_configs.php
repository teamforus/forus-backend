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
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->integer('implementation_id')
                ->unsigned()
                ->nullable()
                ->index()->after('fund_id');

            $table->foreign('implementation_id')
                ->references('id')
                ->on('implementations')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropForeign('fund_configs_implementation_id_foreign');
            $table->dropColumn('implementation_id');
        });
    }
};
