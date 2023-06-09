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
        Schema::table('identity_proxies', function (Blueprint $table) {
            $table->uuid('identity_2fa_uuid')->nullable()->after('state');
            $table->string('identity_2fa_code', 200)->nullable()->after('identity_2fa_uuid');
            $table->unsignedInteger('identity_2fa_parent_proxy_id')->nullable()->after('identity_2fa_code');

            $table->foreign('identity_2fa_uuid')
                ->references('uuid')
                ->on('identity_2fa')
                ->onDelete('restrict');

            $table->foreign('identity_2fa_parent_proxy_id')
                ->references('id')
                ->on('identity_proxies')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('identity_proxies', function (Blueprint $table) {
            $table->dropForeign('identity_proxies_identity_2fa_uuid_foreign');
            $table->dropForeign('identity_proxies_identity_2fa_parent_proxy_id_foreign');
            $table->dropColumn('identity_2fa_uuid');
            $table->dropColumn('identity_2fa_code');
        });
    }
};
