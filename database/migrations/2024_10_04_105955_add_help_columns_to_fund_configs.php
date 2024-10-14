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
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->boolean('help_enabled')
                ->default(false)
                ->after('provider_products_required');

            $table->string('help_title')->nullable()->after('help_enabled');
            $table->string('help_block_text')->nullable()->after('help_title');
            $table->string('help_button_text')->nullable()->after('help_block_text');
            $table->string('help_email')->nullable()->after('help_button_text');
            $table->string('help_phone')->nullable()->after('help_email');
            $table->string('help_website')->nullable()->after('help_phone');
            $table->string('help_chat')->nullable()->after('help_website');
            $table->text('help_description')->nullable()->after('help_chat');
            $table->boolean('help_show_email')->default(true)->after('help_description');
            $table->boolean('help_show_phone')->default(true)->after('help_show_email');
            $table->boolean('help_show_website')->default(true)->after('help_show_phone');
            $table->boolean('help_show_chat')->default(true)->after('help_show_website');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn([
                'help_enabled', 'help_title', 'help_block_text', 'help_button_text',
                'help_email', 'help_phone', 'help_website', 'help_chat', 'help_description',
                'help_show_email', 'help_show_phone', 'help_show_website', 'help_show_chat',
            ]);

        });
    }
};
