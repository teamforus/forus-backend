<?php

namespace App\Digests;

use App\Mail\Digest\BaseDigestMail;
use App\Mail\Digest\DigestSponsorProductUpdatesMail;
use App\Mail\MailBodyBuilder;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\ProductQuery;
use App\Services\Forus\Notification\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;

class SponsorProductUpdatesDigest extends BaseOrganizationDigest
{
    use Dispatchable;

    protected string $requiredRelation = 'funds_active.fund_providers';
    protected string $digestKey = 'sponsor_product_updates';

    protected array $employeePermissions = [
        'manage_providers'
    ];

    /**
     * @param Organization $organization
     * @param NotificationService $notificationService
     */
    public function handleOrganizationDigest(
        Organization $organization,
        NotificationService $notificationService,
    ): void {
        $emailBody = new MailBodyBuilder();
        $emailBody->h1(trans('digests/sponsor_product_updates.title'), ['text_center']);

        $emailBody->text(trans('digests/sponsor.greetings', [
            'organization_name' => $organization->name,
        ]), ["text_center"]);

        $numberOfChanges = $this->getUpdatedProductsQuery($organization)->count();

        $emailBody->text(trans('digests/sponsor_product_updates.details', [
            'nr_changes' => $numberOfChanges,
            'sponsor_dashboard_link' => Implementation::general()->urlProviderDashboard(),
        ]), ['text_center']);

        $emailBody->space();

        $emailBody->button_primary(
            Implementation::general()->urlSponsorDashboard("/organisaties/$organization->id/producten?view=history"),
            trans('digests/sponsor_product_updates.dashboard_button')
        );

        if ($numberOfChanges > 0) {
            $this->sendOrganizationDigest($organization, $emailBody, $notificationService);
        }
    }

    /**
     * @param Organization $organization
     * @return Builder
     */
    private function getUpdatedProductsQuery(Organization $organization): Builder
    {
        return ProductQuery::approvedForFundsFilter(
            Product::query(),
            $organization->funds_active->pluck('id')->toArray(),
        )->whereHas('logs_monitored_field_changed', function(Builder $builder) use ($organization) {
            $builder->where('created_at', '>=', $this->getLastOrganizationDigestTime($organization));
        });
    }

    /**
     * @param MailBodyBuilder $emailBody
     * @return BaseDigestMail
     */
    protected function getDigestMailable(MailBodyBuilder $emailBody): BaseDigestMail
    {
        return new DigestSponsorProductUpdatesMail(compact('emailBody'));
    }
}