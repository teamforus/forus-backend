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
        Schema::create('fund_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('fund_id')->unsigned();
            $table->string('key', 40)->default('');
            $table->string('bunq_key', 200)->default('');
            $table->string('bunq_allowed_ip', 255)->default('');
            $table->boolean('bunq_sandbox')->default(true);
            $table->decimal('formula_amount', 10, 2)->default(0);
            $table->string('formula_multiplier', 40)->default('');
            $table->timestamps();

            $table->foreign('fund_id')
                ->references('id')
                ->on('funds')
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
        Schema::dropIfExists('fund_configs');
    }
};
