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
        Schema::create('identity_bsn_locks', function (Blueprint $table) {
            $table->string('bsn', 9)->primary();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_bsn_locks');
    }
};
