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
        Schema::create('dcp_connections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('implementation_id')->unique();
            $table->boolean('enabled')->default(false);
            $table->string('url', 200);
            $table->string('username', 200)->nullable();
            $table->text('password')->nullable();
            $table->json('order_types')->nullable();
            $table->timestamps();

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
        Schema::dropIfExists('dcp_connections');
    }
};
