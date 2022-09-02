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
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->boolean('email_required')->default(true)
                ->after('is_configured');

            $table->boolean('contact_info_enabled')->default(true)
                ->after('email_required');

            $table->boolean('contact_info_required')->default(true)
                ->after('contact_info_enabled');

            $table->boolean('contact_info_message_custom')->default(false)
                ->after('contact_info_required');

            $table->text('contact_info_message_text')->nullable()->default(null)
                ->after('contact_info_message_custom');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->dropColumn([
                'email_required', 'contact_info_enabled', 'contact_info_required',
                'contact_info_message_custom', 'contact_info_message_text',
            ]);
        });
    }
};
