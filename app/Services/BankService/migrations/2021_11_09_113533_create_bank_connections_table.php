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
        Schema::create('bank_connections', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('bank_id')->index();
            $table->unsignedInteger('organization_id')->index();
            $table->unsignedInteger('implementation_id')->index();
            $table->string('monetary_account_id')->default('');
            $table->string('monetary_account_iban');
            $table->string('redirect_token', 200);
            $table->string('access_token', 200);
            $table->string('code', 200);
            $table->json('context');
            $table->timestamp('session_expire_at')->nullable()->default(null);
            $table->string('state', 50);
            $table->timestamps();

            $table->foreign('bank_id')
                ->references('id')->on('banks')
                ->onDelete('RESTRICT');

            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->onDelete('RESTRICT');

            $table->foreign('implementation_id')
                ->references('id')->on('implementations')
                ->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_connections');
    }
};
