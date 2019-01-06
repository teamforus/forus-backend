<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrganizationPublicInfoFlags extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('organizations', function(Blueprint $table) {
            $table->boolean('email_public')->default(false)->after('email');
            $table->boolean('phone_public')->default(false)->after('phone');
            $table->boolean('website_public')->default(false)->after('website');
        });

        DB::table('organizations')->where([])->update([
            'email_public' => true,
            'phone_public' => true,
            'website_public' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('organizations', function(Blueprint $table) {
            $table->dropColumn('email_public');
            $table->dropColumn('phone_public');
            $table->dropColumn('website_public');
        });
    }
}
