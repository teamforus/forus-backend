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
        Schema::table('fund_configs', function (Blueprint $table) {
            $table->text('backoffice_certificate')->change();
            $table->text('backoffice_client_cert')->change();
            $table->text('backoffice_client_cert_key')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
