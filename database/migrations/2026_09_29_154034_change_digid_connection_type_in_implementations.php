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
            $table->enum('digid_connection_type', ['cgi', 'saml', 'tvs'])->default('cgi')->change();
            $table->text('digid_tvs_idp_cert')->nullable()->after('digid_cgi_tls_cert');
            $table->text('digid_tvs_idp_cert_data')->nullable()->after('digid_tvs_idp_cert');
            $table->text('digid_tvs_sp_cert')->nullable()->after('digid_tvs_idp_cert_data');
            $table->text('digid_tvs_sp_private_key')->nullable()->after('digid_tvs_sp_cert');
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
                'digid_tvs_idp_cert', 'digid_tvs_idp_cert_data', 'digid_tvs_sp_cert', 'digid_tvs_sp_private_key',
            ]);
        });
    }
};
