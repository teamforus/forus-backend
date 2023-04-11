<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\NotificationTemplate;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasColumn('notification_templates', 'fund_id')) {
            return;
        }

        Schema::table('notification_templates', function (Blueprint $table) {
            $table->unsignedInteger('fund_id')->after('implementation_id')->nullable();

            $table->foreign('fund_id')
                ->references('id')->on('funds')
                ->onDelete('cascade');

            $table->dropUnique('notification_templates_fields_unique');

            $table->unique([
                'type', 'formal', 'system_notification_id', 'implementation_id', 'fund_id',
            ], 'notification_templates_fields_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
};
