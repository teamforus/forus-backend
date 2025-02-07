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
        Schema::create('translation_values', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');
            $table->string('key', 200);
            $table->string('locale', 10);
            $table->unsignedInteger('organization_id')->nullable();
            $table->unsignedInteger('implementation_id')->nullable();
            $table->text('from');
            $table->unsignedInteger('from_length');
            $table->text('to');
            $table->unsignedInteger('to_length');
            $table->softDeletes();
            $table->timestamps();

            // Adding foreign key constraints
            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->onDelete('restrict');

            $table->foreign('implementation_id')
                ->references('id')->on('implementations')
                ->onDelete('restrict');

            $table->index(['key']);
            $table->index(['created_at']);
            $table->index(['organization_id', 'locale']);
            $table->index(['translatable_id', 'translatable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translation_values');
    }
};
