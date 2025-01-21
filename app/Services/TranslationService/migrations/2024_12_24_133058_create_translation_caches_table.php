<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_caches', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');
            $table->string('key', 200);
            $table->text('value');
            $table->string('locale', 10);
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'key', 'locale'], 'unique_translation_locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_caches');
    }
};
