<?php

use App\Services\Forus\Auth2FAService\Seeders\Auth2FAProvidersTableSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::create('auth_2fa_providers', function (Blueprint $table) {
            $table->id();
            $table->string('key', 200);
            $table->string('name', 200);
            $table->enum('type', ['phone', 'authenticator']);
            $table->string('url_ios', 2000)->nullable();
            $table->string('url_android', 2000)->nullable();
            $table->timestamps();
        });

        (new Auth2FAProvidersTableSeeder())->run();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_2fa_providers');
    }
};
