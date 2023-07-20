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
        Schema::create('organization_contacts', function (Blueprint $table) {
            $table->id();
            $table->integer('organization_id')->unsigned();
            $table->string('type');
            $table->string('contact_key');
            $table->string('value')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');
        });

        $emails = DB::table('organizations')
            ->whereNotNull('low_balance_email')
            ->pluck('low_balance_email', 'id');

        foreach ($emails as $id => $email) {
            DB::table('organization_contacts')->insert([
                'type' => 'email',
                'value' => $email,
                'contact_key' => 'fund_balance_low',
                'organization_id' => $id,
            ]);
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('low_balance_email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('low_balance_email', 200)->nullable()->after('website_public');
        });

        $emails = DB::table('organization_contacts')
            ->whereNotNull('value')
            ->where('type', 'email')
            ->where('contact_key', 'fund_balance_low')
            ->pluck('value', 'organization_id');

        foreach ($emails as $id => $email) {
            DB::table('organizations')
                ->where(compact('id'))
                ->update([
                    'low_balance_email' => $email,
                ]);
        }

        Schema::dropIfExists('organization_contacts');
    }
};
