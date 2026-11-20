<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('profile_record_groups', function (Blueprint $table) {
            $table->id();
            $table->string('key', 200)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('profile_record_group_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('profile_record_group_id');
            $table->string('locale', 20);
            $table->string('name', 200);
            $table->unique(['profile_record_group_id', 'locale'], 'profile_record_group_locale_unique');

            $table->foreign('profile_record_group_id', 'profile_group_translation_group_fk')
                ->references('id')
                ->on('profile_record_groups')
                ->cascadeOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_record_group_translations');
        Schema::dropIfExists('profile_record_groups');
    }
};
