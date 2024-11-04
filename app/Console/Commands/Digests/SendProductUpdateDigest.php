<?php

namespace App\Console\Commands\Digests;

use App\Http\Requests\BaseFormRequest;
use App\Mail\Product\ProductUpdateMail;
use App\Models\FundProvider;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;

/**
 * Class SendAllDigestsCommand
 * @package App\Console\Commands
 */
class SendProductUpdateDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forus.sponsor_product_digest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send product update digest';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $sponsorOrganizations = Organization::query()->where(
            'is_sponsor', true
        )->whereHas('fund_providers')->get();

        $emailFrom = Implementation::general()->getEmailFrom();

        foreach ($sponsorOrganizations as $sponsor) {
            $fundProviders = FundProvider::search(
                new BaseFormRequest(), $sponsor
            )->pluck('organization_id')->toArray();

            $updatedProducts = ProductQuery::whereNotExpired(
                Product::query()->whereIn('organization_id', $fundProviders)
            )->whereHas('logs', function (Builder $query) {
                $query->where('event', Product::EVENT_UPDATED_DIGEST);
                $query->whereDay('created_at', today());
            })->get();

            if (count($updatedProducts)) {
                $data = [
                    'nr_changes' => count($updatedProducts),
                    'sponsor_dashboard_link' => Implementation::general()->urlProviderDashboard(),
                ];

                $mailable = new ProductUpdateMail($data, $emailFrom);

                resolve('forus.services.notification')->sendSystemMail($sponsor->identity->email, $mailable);
            }
        }
    }
}
