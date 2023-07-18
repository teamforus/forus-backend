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
        Schema::create('identity_2fa_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10);
            $table->uuid('identity_2fa_uuid')->index();
            $table->timestamp('expire_at')->nullable()->default(null);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('identity_2fa_uuid')
                ->references('uuid')
                ->on('identity_2fa')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_2fa_codes');
    }
};
