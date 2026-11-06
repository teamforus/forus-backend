<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * @return void
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->enum('deletion_reason', ['manual', 'provisioning'])->nullable()->after('deleted_at');
        });

        DB::table('employees')->whereNotNull('deleted_at')->update([
            'deletion_reason' => 'manual',
        ]);
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropColumn('deletion_reason');
        });
    }
};
