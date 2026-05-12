<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->boolean('auth_page_login_openid')->default(false)->after('auth_page_login_digid');
            $table->boolean('openid_verid_enabled')->default(false)->after('allow_per_fund_notification_templates');
            $table->json('openid_verid_context')->nullable()->after('openid_verid_enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropColumn([
                'auth_page_login_openid',
                'openid_verid_enabled',
                'openid_verid_context',
            ]);
        });
    }
};
