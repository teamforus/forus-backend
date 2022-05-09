<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * @noinspection PhpUnused
 */
class CreateFundProviderInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('fund_provider_invitations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('organization_id');
            $table->unsignedInteger('from_fund_id');
            $table->unsignedInteger('fund_id');
            $table->boolean('allow_budget')->default(false);
            $table->boolean('allow_products')->default(false);
            $table->enum('state', [
                'pending', 'accepted', 'expired'
            ])->default('pending');
            $table->string('token', 200);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_provider_invitations');
    }
}
