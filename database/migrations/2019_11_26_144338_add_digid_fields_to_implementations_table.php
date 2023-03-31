<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('implementations', function(Blueprint $table) {
            $table->boolean('digid_enabled')->default(false)->after('lat');
            $table->enum('digid_env', ['sandbox', 'production'])->default('sandbox')->after('digid_enabled');
            $table->enum('digid_connection_type', ['cgi', 'saml'])->default('cgi')->after('digid_enabled');
            $table->json('digid_saml_context')->nullable()->after('digid_connection_type');
            $table->string('digid_app_id',  '100')->nullable()->after('digid_env');
            $table->string('digid_shared_secret',  '100')->nullable()->after('digid_app_id');
            $table->string('digid_a_select_server',  '100')->nullable()->after('digid_shared_secret');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('implementations', function(Blueprint $table) {
            $table->dropColumn('digid_enabled');
            $table->dropColumn('digid_connection_type');
            $table->dropColumn('digid_saml_context');
            $table->dropColumn('digid_env');
            $table->dropColumn('digid_app_id');
            $table->dropColumn('digid_shared_secret');
            $table->dropColumn('digid_a_select_server');
        });
    }
};
