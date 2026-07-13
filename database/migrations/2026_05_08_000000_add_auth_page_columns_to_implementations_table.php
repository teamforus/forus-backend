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
        Schema::table('implementations', function (Blueprint $table) {
            $table->string('auth_page_title', 100)->default('Inloggen')->after('products_default_sorting');
            $table->string('auth_page_login_title', 100)
                ->default('Kies hoe u wilt inloggen of registreren')
                ->after('auth_page_title');
            $table->boolean('auth_page_login_email')->default(true)->after('auth_page_login_title');
            $table->boolean('auth_page_login_digid')->default(false)->after('auth_page_login_email');
            $table->boolean('auth_page_login_qr')->default(true)->after('auth_page_login_digid');
            $table->boolean('auth_page_info_enabled')->default(false)->after('auth_page_login_qr');
            $table->string('auth_page_info_title', 100)->nullable()->after('auth_page_info_enabled');
            $table->text('auth_page_info_description')->nullable()->after('auth_page_info_title');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropColumn([
                'auth_page_title',
                'auth_page_login_title',
                'auth_page_login_email',
                'auth_page_login_digid',
                'auth_page_login_qr',
                'auth_page_info_enabled',
                'auth_page_info_title',
                'auth_page_info_description',
            ]);
        });
    }
};
