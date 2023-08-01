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
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('allow_reservation_custom_fields')->default(false)
                ->after('allow_bi_connection');
        });

        Schema::create('organization_reservation_fields', function (Blueprint $table) {
            $table->id();
            $table->integer('organization_id')->unsigned();
            $table->string('label');
            $table->string('type', 10)->default('text');
            $table->text('description')->nullable();
            $table->boolean('required')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('allow_reservation_custom_fields');
        });

        Schema::dropIfExists('organization_reservation_fields');
    }
};
