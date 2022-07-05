<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class AddRedeemByIdentityAddressToPrevalidationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('prevalidations', function (Blueprint $table) {
            $table->string('redeemed_by_address')->nullable()->after('identity_address');

            $table->foreign('redeemed_by_address'
            )->references('address')->on('identities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('prevalidations', function (Blueprint $table) {
            $table->dropForeign('prevalidations_redeemed_by_address_foreign');
            $table->dropColumn('redeemed_by_address');
        });
    }
}
