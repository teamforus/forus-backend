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
        Schema::create('fund_config_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('fund_id');
            $table->string('record_type');
            $table->unsignedMediumInteger('record_validity_days')->nullable();
            $table->timestamps();
            $table->unique(['fund_id', 'record_type']);
            $table->index(['fund_id', 'record_type']);

            $table->foreign('fund_id')->references('id')->on('funds')
                ->onDelete('cascade');

            $table->foreign('record_type')->references('key')->on('record_types')
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
        Schema::table('fund_config_records', static function(Blueprint $table) {
            $table->dropForeign('fund_config_records_fund_id_foreign');
            $table->dropForeign('fund_config_records_record_type_foreign');
        });

        Schema::dropIfExists('fund_config_records');
    }
};
