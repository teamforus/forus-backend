<?php

namespace App\Console\Commands\Digests;

use App\Mail\Product\ProductUpdateMail;
use App\Models\FundProvider;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Console\Command;

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
        )->whereHas('funds_active.fund_providers')->get();

        $emailFrom = Implementation::general()->getEmailFrom();

        foreach ($sponsorOrganizations as $sponsor) {
            /** @var Organization $sponsor */
            $fundProviders = FundProvider::where([
                'fund_id' => $sponsor->funds_active->pluck('id')->toArray(),
                'state' => FundProvider::STATE_ACCEPTED,
            ])->get()->pluck('organization_id')->toArray();

            if (!count($fundProviders)) {
                return;
            }

            $updatedProducts = ProductQuery::whereNotExpired(
                Product::query()->whereIn('organization_id', $fundProviders)
            )->whereHas('logs', function (\Illuminate\Database\Eloquent\Builder $query) {
                $query->where('event', Product::EVENT_MONITORED_FEILDS_UPDATED);
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
