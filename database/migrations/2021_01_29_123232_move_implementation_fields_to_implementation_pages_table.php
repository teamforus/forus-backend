<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \App\Models\ImplementationPage;
use \App\Models\Implementation;

/**
 * Class MoveImplementationFieldsToImplementationPagesTable
 * @noinspection PhpUnused
 */
class MoveImplementationFieldsToImplementationPagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Implementation::get()->each(static function (Implementation $implementation) {
            if (!$implementation->page_explanation()->exists()) {
                $implementation->pages()->create([
                    'content'       => $implementation->description_steps ?? null,
                    'page_type'     => ImplementationPage::TYPE_EXPLANATION,
                    'external_url'  => $implementation->more_info_url ?? null,
                    'use_external'  => $implementation->has_more_info_url ?? null,
                ]);
            }

            if (!$implementation->page_provider()->exists()) {
                $implementation->pages()->create([
                    'content' => $implementation->description_providers ?? null,
                    'page_type' => ImplementationPage::TYPE_PROVIDER,
                    'external_url' => null,
                    'use_external' => false,
                ]);
            }
        });

        Schema::table('implementations', function (Blueprint $table) {
            $table->dropColumn([
                'description_steps', 'description_providers', 'more_info_url', 'has_more_info_url',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->boolean('has_more_info_url')->after('description')->default(false);
            $table->string('more_info_url', 200)->after('description')->nullable();
            $table->text('description_steps')->after('more_info_url')->nullable();
            $table->text('description_providers')->after('description_steps')->nullable();
        });
    }
}
