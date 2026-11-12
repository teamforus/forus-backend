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
        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedBigInteger('dcp_connection_id')->nullable()->after('allow_voucher_passes');

            $table->foreign('dcp_connection_id', 'organizations_dcp_connection_fk')
                ->references('id')->on('dcp_connections')->restrictOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropForeign('organizations_dcp_connection_fk');
            $table->dropColumn('dcp_connection_id');
        });
    }
};
