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
        Schema::create('external_identities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uid')->unique();
            $table->string('provider', 32);
            $table->uuid('tenant_id');
            $table->uuid('object_id');
            $table->string('issuer', 500)->collation('utf8_bin')->nullable();
            $table->string('subject', 255)->collation('utf8_bin')->nullable();
            $table->unsignedInteger('identity_id')->nullable()->index();
            $table->timestamp('last_authenticated_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'tenant_id', 'object_id'], 'external_identities_provider_object_unique');
            $table->unique(['provider', 'issuer', 'subject'], 'external_identities_provider_subject_unique');
            $table->foreign('identity_id')->references('id')->on('identities')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('external_identities');
    }
};
