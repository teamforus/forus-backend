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
        Schema::create('implementation_cms_block_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('implementation_cms_block_id');
            $table->string('field_key', 100);
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->unique(
                ['implementation_cms_block_id', 'field_key'],
                'cms_block_values_block_field_unique',
            );
            $table->index('field_key', 'cms_block_values_field_index');

            $table->foreign('implementation_cms_block_id', 'cms_block_values_block_foreign')
                ->references('id')
                ->on('implementation_cms_blocks')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('implementation_cms_block_values');
    }
};
