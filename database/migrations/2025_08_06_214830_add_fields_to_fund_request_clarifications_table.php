<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fund_request_clarifications', function (Blueprint $table) {
            $table->enum('text_requirement', ['no', 'optional', 'required'])
                ->default('required')
                ->after('question');

            $table->enum('files_requirement', ['no', 'optional', 'required'])
                ->default('required')
                ->after('text_requirement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fund_request_clarifications', function (Blueprint $table) {
            $table->dropColumn([
                'text_requirement',
                'files_requirement',
            ]);
        });
    }
};
