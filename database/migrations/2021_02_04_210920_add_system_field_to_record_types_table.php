<?php

use App\Models\RecordType;
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
        Schema::table('record_types', function (Blueprint $table) {
            $table->boolean('system')->after('type')->default(1);
        });

        RecordType::get()->each(fn(RecordType $type) => $type->update([
            'system' => $this->isSystemRecordType($type),
        ]));
    }

    /**
     * @param RecordType $recordType
     * @return bool
     */
    protected function isSystemRecordType(RecordType $recordType): bool
    {
        return
            ends_with($recordType->key, '_eligible_nth') ||
            ends_with($recordType->key, '_eligible') ||
            ends_with($recordType->key, '_hash') ||
            in_array($recordType->key, ['bsn', 'partner_bsn', 'uid', 'primary_email'], true);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('record_types', fn(Blueprint $table) => $table->dropColumn('system'));
    }
};
