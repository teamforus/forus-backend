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
        Schema::create('implementation_cms_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('implementation_page_id');
            $table->string('block_type_key', 80);
            $table->unsignedInteger('order')->default(0);
            $table->enum('state', ['draft', 'public'])->default('draft');
            $table->timestamps();

            $table->index(
                ['implementation_page_id', 'state', 'order'],
                'cms_blocks_page_state_order_index',
            );

            $table->index(
                ['implementation_page_id', 'order'],
                'cms_blocks_page_order_index',
            );

            $table->foreign('implementation_page_id', 'cms_blocks_page_foreign')
                ->references('id')
                ->on('implementation_pages')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('implementation_cms_blocks');
    }
};
