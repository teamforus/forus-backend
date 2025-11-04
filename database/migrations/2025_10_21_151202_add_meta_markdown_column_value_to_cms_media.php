<?php

use App\Models\Announcement;
use App\Models\Faq;
use App\Models\Fund;
use App\Models\FundCriteriaStep;
use App\Models\Implementation;
use App\Models\ImplementationBlock;
use App\Models\ImplementationPage;
use App\Models\Organization;
use App\Models\Product;
use App\Services\MediaService\Models\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Media::query()
            ->whereIn('mediable_type', [
                (new Announcement())->getMorphClass(),
                (new Faq())->getMorphClass(),
                (new Fund())->getMorphClass(),
                (new FundCriteriaStep())->getMorphClass(),
                (new Implementation())->getMorphClass(),
                (new ImplementationBlock())->getMorphClass(),
                (new ImplementationPage())->getMorphClass(),
                (new Organization())->getMorphClass(),
                (new Product())->getMorphClass(),
            ])
            ->where('type', 'cms_media')
            ->where(function (Builder $builder) {
                $builder->whereNull('meta');
                $builder->orWhereJsonDoesntContainKey('meta->markdown_column');
            })
            ->update(['meta' => ['markdown_column' => 'description']]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
