<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @noinspection PhpUnused
 */
class CreateFundProviderChatMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('fund_provider_chat_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('fund_provider_chat_id')->unsigned();
            $table->string('message', 2000);
            $table->string('identity_address', 200)->nullable();
            $table->string('counterpart', 50);
            $table->timestamp('seen_at')->nullable()->default(null);
            $table->boolean('provider_seen')->default(false);
            $table->boolean('sponsor_seen')->default(false);
            $table->timestamps();

            $table->foreign('fund_provider_chat_id'
            )->references('id')->on('fund_provider_chats')->onDelete('cascade');

            $table->foreign('identity_address'
            )->references('address')->on('identities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_provider_chat_messages');
    }
}
