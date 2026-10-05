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
            $table->boolean('openid_enabled')->default(false)->after('allow_per_fund_notification_templates');
            $table->uuid('openid_verid_brand_uuid')->nullable()->after('openid_enabled');
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
                'openid_enabled',
                'openid_verid_brand_uuid',
            ]);
        });
    }
};
