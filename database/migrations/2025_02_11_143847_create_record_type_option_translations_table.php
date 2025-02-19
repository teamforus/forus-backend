<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('record_type_option_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('record_type_option_id');
            $table->string('locale')->index();
            $table->string('name', 200);

            $table->unique(['record_type_option_id', 'locale'], 'record_type_option_translations_id_locale_unique');

            $table->foreign('record_type_option_id')
                ->references('id')
                ->on('record_type_options')
                ->onDelete('cascade');
        });

        $options = DB::table('record_type_options')->get();

        foreach ($options as $option) {
            DB::table('record_type_option_translations')->insert([
                'record_type_option_id' => $option->id,
                'name' => $option->name,
                'locale' => 'nl',
            ]);
        }

        Schema::table('record_type_options', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('record_type_options', function (Blueprint $table) {
            $table->string('name', 200)->after('value');
        });

        $translations = DB::table('record_type_option_translations')
            ->where('locale', 'nl')
            ->get();

        foreach ($translations as $translation) {
            DB::table('record_type_options')
                ->where('id', $translation->record_type_option_id)
                ->update(['name' => $translation->name]);
        }

        Schema::dropIfExists('record_type_option_translations');
    }
};
