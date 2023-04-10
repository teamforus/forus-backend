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
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('key', 200)->nullable()->after('type');
            $table->string("announceable_type")->nullable()->after('implementation_id');
            $table->unsignedBigInteger("announceable_id")->nullable()->after('announceable_type');
            $table->boolean('dismissible')->default(true)->after('announceable_id');

            $table->index(["announceable_type", "announceable_id"]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('key');
            $table->dropMorphs('announceable');
            $table->dropColumn('dismissible');
        });
    }
};
