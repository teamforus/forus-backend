<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ImplementationPage;
use App\Models\Implementation;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Implementation::get()->each(function (Implementation $implementation) {
            if (!$this->withTrashed($implementation->page_explanation())->exists()) {
                $implementation->pages()->create([
                    'content'       => $implementation->description_steps ?? null,
                    'page_type'     => ImplementationPage::TYPE_EXPLANATION,
                    'external_url'  => $implementation->more_info_url ?? null,
                    'use_external'  => $implementation->has_more_info_url ?? null,
                ]);
            }

            if (!$this->withTrashed($implementation->page_provider())->exists()) {
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

    private function withTrashed(Builder|Relation $builder): Builder|Relation
    {
        /** @var Builder|Relation|SoftDeletes $builder */
        return $builder->withTrashed();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('implementations', function (Blueprint $table) {
            $table->boolean('has_more_info_url')->after('description')->default(false);
            $table->string('more_info_url', 200)->after('description')->nullable();
            $table->text('description_steps')->after('more_info_url')->nullable();
            $table->text('description_providers')->after('description_steps')->nullable();
        });
    }
};