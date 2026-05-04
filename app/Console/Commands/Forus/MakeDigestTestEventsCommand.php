<?php

namespace App\Console\Commands\Forus;

use App\Console\Commands\BaseCommand;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundRequest;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Scopes\Builders\ProductQuery;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * Generates fresh digest-visible activity so the digest Artisan commands have
 * something to send when running them locally. For each digest type it appends
 * EventLog rows via the regular HasLogs::log() path; no seeded rows are mutated.
 * Local QA only; refuses to run on production.
 *
 * Use `--mode=singular` to write one event per digest type and `--mode=plural`
 * to write two, so trans_choice singular and plural strings can both be checked.
 *
 * Prerequisites:
 * - The database has been seeded once (see docs/seeding-test-data.md).
 * - A local mail catcher (e.g. Mailpit) is configured if you want to inspect
 *   the resulting digest emails.
 *
 * Out of scope: sponsor digest subsections that key off FundProvider.created_at
 * or Product.created_at; covering them would require seeding new rows.
 *
 * Related docs:
 * - docs/features/digest-emails.md — what digests are and how they pick events.
 * - docs/testing/email.md — full local digest testing flow and follow-up commands.
 */
class MakeDigestTestEventsCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test-data:make-digest-events {--mode=singular : `singular` or `plural` (trans_choice coverage)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add digest-visible activity on top of seeded test data (local QA only).';

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        if (config('app.env') == 'production') {
            throw new Exception("Can't be used on production.");
        }

        $mode = strtolower((string) $this->option('mode'));

        if (!in_array($mode, ['singular', 'plural'], true)) {
            $this->error('Invalid --mode. Use `singular` or `plural`.');

            return;
        }

        $count = $mode === 'plural' ? 2 : 1;

        $providerOrg = Organization::query()
            ->where('is_provider', true)
            ->whereHas('fund_providers', fn (Builder $q) => $q->where('state', FundProvider::STATE_ACCEPTED))
            ->orderBy('id')
            ->first();

        $sponsorOrg = Organization::query()
            ->where('is_sponsor', true)
            ->whereHas('funds', fn (Builder $q) => $q->where('state', Fund::STATE_ACTIVE))
            ->orderByDesc('allow_product_updates')
            ->orderBy('id')
            ->first();

        if (!$providerOrg || !$sponsorOrg) {
            $this->error('Missing seeded provider or sponsor organization. Run `php artisan test-data:seed` first.');

            return;
        }

        $startTime = microtime(true);
        $this->info("⇾ Making digest events ($count per type)!");

        $this->writeProviderFundsEvents($providerOrg, $count);
        $this->writeProviderProductsEvents($providerOrg, $count);
        $this->writeProviderReservationsEvents($providerOrg, $count);
        $this->writeRequesterEvents($providerOrg, $count);
        $this->writeValidatorEvents($sponsorOrg, $count);
        $this->writeSponsorEvents($sponsorOrg);
        $this->writeSponsorProductUpdatesEvents($sponsorOrg, $count);

        $this->info('✓ Digest events created in ' . round(microtime(true) - $startTime, 2) . 's.');
    }

    /**
     * @param Organization $provider
     * @param int $count
     */
    private function writeProviderFundsEvents(Organization $provider, int $count): void
    {
        $fundProviders = FundProvider::query()
            ->where('organization_id', $provider->id)
            ->where('state', FundProvider::STATE_ACCEPTED)
            ->with(['fund.organization', 'organization'])
            ->orderBy('id')
            ->limit($count)
            ->get();

        foreach ($fundProviders as $fundProvider) {
            $fundProvider->log(FundProvider::EVENT_APPROVED_BUDGET, [
                'fund' => $fundProvider->fund,
                'sponsor' => $fundProvider->fund->organization,
                'provider' => $fundProvider->organization,
                'implementation' => $fundProvider->fund->getImplementation(),
            ]);
        }

        $product = Product::query()
            ->where('organization_id', $provider->id)
            ->where(fn (Builder $q) => ProductQuery::approvedForFundsFilter(
                $q,
                $fundProviders->pluck('fund_id')->all(),
            ))
            ->first();

        if ($product && $fund = $fundProviders->first()?->fund) {
            $product->log(Product::EVENT_APPROVED, [
                'fund' => $fund,
                'product' => $product,
                'sponsor' => $fund->organization,
                'provider' => $product->organization,
                'implementation' => $fund->getImplementation(),
            ]);
        }
    }

    /**
     * @param Organization $provider
     * @param int $count
     */
    private function writeProviderProductsEvents(Organization $provider, int $count): void
    {
        $vouchers = Voucher::query()
            ->whereNotNull('product_id')
            ->whereHas('product', fn (Builder $q) => $q->where('organization_id', $provider->id))
            ->with(['fund.organization', 'product.organization', 'product_reservation'])
            ->orderBy('id')
            ->limit($count)
            ->get();

        foreach ($vouchers as $voucher) {
            $product = $voucher->product;

            if (!$product || !$voucher->fund) {
                continue;
            }

            $product->log(Product::EVENT_RESERVED, [
                'fund' => $voucher->fund,
                'sponsor' => $voucher->fund->organization,
                'product' => $product,
                'provider' => $product->organization,
                'implementation' => $voucher->fund->getImplementation(),
                'product_reservation' => $voucher->product_reservation,
            ], [
                'expiration_date' => format_date_locale($voucher->last_active_day),
            ]);
        }
    }

    /**
     * @param Organization $provider
     * @param int $count
     */
    private function writeProviderReservationsEvents(Organization $provider, int $count): void
    {
        $reservations = ProductReservation::query()
            ->whereHas('product', fn (Builder $q) => $q->where('organization_id', $provider->id))
            ->with(['product.organization', 'voucher.fund.organization'])
            ->orderBy('id')
            ->limit($count)
            ->get();

        foreach ($reservations as $reservation) {
            $voucher = $reservation->voucher;
            $fund = $voucher?->fund;

            if (!$fund) {
                continue;
            }

            $models = [
                'fund' => $fund,
                'product' => $reservation->product,
                'sponsor' => $fund->organization,
                'provider' => $reservation->product->organization,
                'voucher' => $voucher,
                'implementation' => $fund->getImplementation(),
                'product_reservation' => $reservation,
            ];

            $reservation->log(ProductReservation::EVENT_CREATED, $models);
            $reservation->log(ProductReservation::EVENT_ACCEPTED, $models);
        }
    }

    /**
     * @param Organization $provider
     * @param int $count
     */
    private function writeRequesterEvents(Organization $provider, int $count): void
    {
        $fundProviders = FundProvider::query()
            ->where('organization_id', $provider->id)
            ->where('state', FundProvider::STATE_ACCEPTED)
            ->with(['fund.organization', 'organization'])
            ->orderBy('id')
            ->limit($count)
            ->get();

        foreach ($fundProviders as $fundProvider) {
            $fund = $fundProvider->fund;

            $fund->log(Fund::EVENT_PROVIDER_APPROVED_BUDGET, [
                'fund' => $fund,
                'sponsor' => $fund->organization,
                'fund_config' => $fund->fund_config,
                'implementation' => $fund->getImplementation(),
                'provider' => $fundProvider->organization,
            ]);
        }
    }

    /**
     * @param Organization $sponsor
     * @param int $count
     */
    private function writeValidatorEvents(Organization $sponsor, int $count): void
    {
        $fundRequests = FundRequest::query()
            ->whereHas('fund', fn (Builder $q) => $q->where('organization_id', $sponsor->id))
            ->with('fund')
            ->orderBy('id')
            ->limit($count)
            ->get();

        foreach ($fundRequests as $fundRequest) {
            $fundRequest->log(FundRequest::EVENT_CREATED, [
                'fund' => $fundRequest->fund,
                'fund_request' => $fundRequest,
            ]);
        }
    }

    /**
     * @param Organization $sponsor
     */
    private function writeSponsorEvents(Organization $sponsor): void
    {
        foreach ($sponsor->funds_active as $fund) {
            $product = Product::query()
                ->whereHas('fund_provider_chats.fund_provider', fn (Builder $q) => $q->where('fund_id', $fund->id))
                ->with('fund_provider_chats.fund_provider.organization')
                ->first();

            if (!$product || $product->fund_provider_chats->isEmpty()) {
                continue;
            }

            $providerOrg = $product->fund_provider_chats->first()->fund_provider->organization;

            $fund->log(Fund::EVENT_PROVIDER_REPLIED, [
                'fund' => $fund,
                'sponsor' => $fund->organization,
                'fund_config' => $fund->fund_config,
                'implementation' => $fund->getImplementation(),
                'product' => $product,
                'provider' => $providerOrg,
            ]);
        }
    }

    /**
     * @param Organization $sponsor
     * @param int $count
     */
    private function writeSponsorProductUpdatesEvents(Organization $sponsor, int $count): void
    {
        if (!$sponsor->allow_product_updates) {
            return;
        }

        $fundIds = $sponsor->funds_active->pluck('id')->all();

        if ($fundIds === []) {
            return;
        }

        $products = Product::query()
            ->where(fn (Builder $q) => ProductQuery::approvedForFundsFilter($q, $fundIds))
            ->with('organization')
            ->orderBy('id')
            ->limit($count)
            ->get();

        foreach ($products as $product) {
            $product->log(Product::EVENT_MONITORED_FIELDS_UPDATED, [
                'product' => $product,
                'provider' => $product->organization,
            ], [
                'product_updated_fields' => ['name'],
            ]);
        }
    }
}
