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
        if (!Schema::hasColumn('implementations', 'digid_connection_type')) {
            Schema::table('implementations', function (Blueprint $table) {
                $table->enum('digid_connection_type', ['cgi', 'saml'])->default('cgi')->after('digid_enabled');
                $table->json('digid_saml_context')->nullable()->after('digid_connection_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
