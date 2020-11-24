<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOrganizationsAddRoleColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('organizations', function(Blueprint $table) {
            $table->boolean('is_sponsor')->default(false)->after('business_type_id');
            $table->boolean('is_provider')->default(false)->after('is_sponsor');
            $table->boolean('is_validator')->default(false)->after('is_provider');
            $table->boolean('validator_auto_accept_funds')->default(false)->after('is_validator');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('organizations', function(Blueprint $table) {
            $table->dropColumn([
                'is_sponsor', 'is_provider', 'is_validator',
                'validator_auto_accept_funds'
            ]);
        });
    }
}
