<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * @noinspection PhpUnused
 */
class MakeVoucherIdentityAddressNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('vouchers', function(Blueprint $table) {
            $table->string('identity_address', 200)->nullable()->change();
            $table->string('note', 280)->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('vouchers', function(Blueprint $table) {
            $table->string('identity_address', 200)->change();
            $table->dropColumn('note');
        });
    }
}
