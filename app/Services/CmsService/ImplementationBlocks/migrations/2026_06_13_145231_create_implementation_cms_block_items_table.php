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
        Schema::create('implementation_cms_block_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('implementation_cms_block_id');
            $table->string('item_type_key', 80);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(
                ['implementation_cms_block_id', 'order'],
                'cms_block_items_block_order_index',
            );

            $table->foreign('implementation_cms_block_id', 'cms_block_items_block_foreign')
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
        Schema::dropIfExists('implementation_cms_block_items');
    }
};
