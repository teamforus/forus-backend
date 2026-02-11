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
        Schema::create('fund_request_record_groups', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedInteger('organization_id')->nullable();
            $table->unsignedInteger('fund_id')->nullable();
            $table->unsignedInteger('order')->default(0);

            $table->timestamps();

            $table
                ->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');

            $table
                ->foreign('fund_id')
                ->references('id')
                ->on('funds')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_request_record_groups');
    }
};
