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
        Schema::create('person_bsn_api_record_types', function (Blueprint $table) {
            $table->id();
            $table->string('person_bsn_api_field');
            $table->string('record_type_key');
            $table->timestamps();

            $table->foreign('record_type_key')
                ->references('key')
                ->on('record_types')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('person_bsn_api_record_types');
    }
};
