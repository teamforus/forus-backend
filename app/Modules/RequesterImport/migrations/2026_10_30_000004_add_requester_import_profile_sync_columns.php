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
        Schema::table('requester_import_rows', function (Blueprint $table) {
            $table->foreignId('profile_id')->nullable()->after('bsn')->constrained('profiles')->restrictOnDelete();
        });

        Schema::table('requester_import_records', function (Blueprint $table) {
            $table->timestamp('synced_at')->nullable()->after('value');
        });

        Schema::table('profile_records', function (Blueprint $table) {
            $table->foreignId('requester_import_record_id')->nullable()->after('source')
                ->constrained('requester_import_records')->restrictOnDelete();
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('profile_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requester_import_record_id');
        });

        Schema::table('requester_import_records', function (Blueprint $table) {
            $table->dropColumn('synced_at');
        });

        Schema::table('requester_import_rows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('profile_id');
        });
    }
};
