<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('identity_id');
            $table->unsignedInteger('organization_id');
            $table->timestamps();

            $table->index(['identity_id', 'organization_id']);
            $table->unique(['identity_id', 'organization_id']);

            $table->foreign('identity_id')
                ->references('id')
                ->on('identities')
                ->onDelete('restrict');

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
