<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 * @noinspection PhpIllegalPsrClassPathInspection
 */
class AddDeletedAtToIdentityProxiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (!Schema::hasColumn('identity_proxies', 'deleted_at')) {
            Schema::table('identity_proxies', function (Blueprint $table) {
                $table->softDeletes()->after('state');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
}
