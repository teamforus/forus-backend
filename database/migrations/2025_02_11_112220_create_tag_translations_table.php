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
        Schema::create('tag_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('tag_id');
            $table->string('locale')->index();
            $table->string('name', 50);

            $table->unique(['tag_id', 'locale']);

            $table->foreign('tag_id')
                ->references('id')
                ->on('tags')
                ->onDelete('cascade');
        });

        $tags = DB::table('tags')->get();

        foreach ($tags as $tag) {
            DB::table('tag_translations')->insert([
                'tag_id' => $tag->id,
                'name' => $tag->name,
                'locale' => 'nl',
            ]);
        }

        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->string('name', 50)->after('scope');
        });

        $translations = DB::table('tag_translations')
            ->where('locale', 'nl')
            ->get();

        foreach ($translations as $translation) {
            DB::table('tags')
                ->where('id', $translation->tag_id)
                ->update(['name' => $translation->name]);
        }

        Schema::dropIfExists('tag_translations');
    }
};
