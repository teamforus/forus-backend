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
        Schema::table('implementations', function (Blueprint $table) {
            $table->text('digid_cgi_tls_key')->nullable()->after('digid_trusted_cert');
            $table->text('digid_cgi_tls_cert')->nullable()->after('digid_cgi_tls_key');
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
            $table->dropColumn('digid_cgi_tls_key');
            $table->dropColumn('digid_cgi_tls_cert');
        });
    }
};