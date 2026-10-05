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
        Schema::create('wallet_disclosures', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('wallet_session_id')->nullable()->unique();
            $table->unsignedBigInteger('wallet_flow_id');
            $table->unsignedInteger('identity_id');
            $table->unsignedInteger('fund_id');
            $table->unsignedInteger('fund_request_id')->nullable()->unique();
            $table->longText('payload');
            $table->longText('records');
            $table->timestamp('verified_at');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['identity_id', 'fund_id']);

            $table->foreign('wallet_session_id')->references('id')->on('wallet_sessions')->nullOnDelete();
            $table->foreign('wallet_flow_id')->references('id')->on('wallet_flows')->restrictOnDelete();
            $table->foreign('identity_id')->references('id')->on('identities')->restrictOnDelete();
            $table->foreign('fund_id')->references('id')->on('funds')->restrictOnDelete();
            $table->foreign('fund_request_id')->references('id')->on('fund_requests')->restrictOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_disclosures');
    }
};
