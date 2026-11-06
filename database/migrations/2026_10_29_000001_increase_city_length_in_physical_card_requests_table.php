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
        Schema::table('physical_card_requests', function (Blueprint $table) {
            $table->string('city', 100)->change();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
    }
};
