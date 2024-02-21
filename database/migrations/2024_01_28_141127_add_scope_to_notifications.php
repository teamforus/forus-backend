<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('key')->nullable()->after('id');
            $table->enum('scope', ['webshop', 'sponsor', 'provider', 'validator'])->nullable()->after('key');
            $table->unsignedInteger('organization_id')->after('scope')->nullable();
            $table->unsignedBigInteger('event_id')->after('organization_id')->nullable();

            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->onDelete('restrict');

            $table->foreign('event_id')
                ->references('id')->on('event_logs')
                ->onDelete('restrict');

            $table->index('scope');
        });

        DB::table('notifications')->orderBy('created_at')->each(function ($notification) {
            $data = json_decode($notification->data, true);

            DB::table('notifications')->where('id', $notification->id)->update([
                'key' => $data['key'] ?? null,
                'scope' => $data['scope'] ?? null,
                'event_id' => $data['event_id'] ?? null,
                'organization_id' => $data['organization_id'] ?? null,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['event_id']);
            $table->dropIndex(['scope']);
            $table->dropColumn(['key', 'scope', 'organization_id', 'event_id']);
        });
    }
};
