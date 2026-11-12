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
        Schema::create('requester_import_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('organization_id');
            $table->char('bsn', 9);
            $table->unsignedBigInteger('profile_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['organization_id', 'bsn'], 'requester_rows_organization_bsn_unique');
            $table->unique(['id', 'organization_id'], 'requester_rows_id_organization_unique');
            $table->foreign('organization_id')->references('id')->on('organizations')->restrictOnDelete();
            $table->foreign('profile_id', 'requester_import_rows_profile_id_foreign')
                ->references('id')->on('profiles')->restrictOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('requester_import_rows');
    }
};
