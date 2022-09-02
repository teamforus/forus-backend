<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::dropIfExists('mail_jobs');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
