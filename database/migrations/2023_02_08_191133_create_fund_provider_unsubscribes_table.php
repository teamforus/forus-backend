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
        Schema::create('fund_provider_unsubscribes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('fund_provider_id');
            $table->text('note')->nullable();
            $table->boolean('canceled')->default(false);
            $table->timestamp('unsubscribe_at');
            $table->timestamps();

            $table->foreign('fund_provider_id')
                ->references('id')->on('fund_providers')
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
        Schema::dropIfExists('fund_provider_unsubscribes');
    }
};
