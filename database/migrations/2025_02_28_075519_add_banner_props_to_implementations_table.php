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
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropColumn('header_text_color');

            $table->string('banner_color', 20)->after('overlay_opacity')->default('#000');
            $table->string('banner_background', 20)->after('banner_color')->default('#fff');

            $table->enum('banner_position', ['left', 'center', 'right'])->after('banner_background')->default('left');
            $table->boolean('banner_wide')->after('banner_position')->default(1);
            $table->boolean('banner_collapse')->after('banner_wide')->default(0);

            $table->boolean('banner_button')->after('banner_background')->default(false);
            $table->text('banner_button_url')->after('banner_button')->default(null);
            $table->text('banner_button_text')->after('banner_button_url')->default(null);
            $table->enum('banner_button_target', ['self', '_blank'])->after('banner_button_text')->default('_blank');
            $table->enum('banner_button_type', ['color', 'white'])->after('banner_button_target')->default('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->enum('header_text_color', ['dark', 'bright', 'auto'])->default('auto')->after('overlay_type');

            $table->dropColumn('banner_color');
            $table->dropColumn('banner_background');
            $table->dropColumn('banner_position');
            $table->dropColumn('banner_wide');
            $table->dropColumn('banner_collapse');
            $table->dropColumn('banner_button');
            $table->dropColumn('banner_button_url');
            $table->dropColumn('banner_button_text');
            $table->dropColumn('banner_button_target');
            $table->dropColumn('banner_button_type');
        });
    }
};
