<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class ChangeExpireStartAndEndDateFormats
 * @noinspection PhpUnused
 */
class ChangeExpireStartAndEndDateFormats extends Migration
{
    /**
     * ChangeExpireAtFormat constructor.
     * @throws \Doctrine\DBAL\Exception
     */
    public function __construct()
    {
        DB::getDoctrineSchemaManager()
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', function(Blueprint $table) {
            $table->date('expire_at')->change();
        });

        Schema::table('funds', function(Blueprint $table) {
            $table->date('start_date')->change();
            $table->date('end_date')->change();
        });

        Schema::table('vouchers', function(Blueprint $table) {
            $table->date('expire_at')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void { }
}
