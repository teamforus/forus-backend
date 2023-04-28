<?php

namespace App\Digests;

use App\Mail\Digest\BaseDigestMail;
use App\Mail\MailBodyBuilder;
use App\Models\Organization;
use App\Services\Forus\Notification\NotificationService;
use Carbon\Carbon;

/**
 * Class BaseOrganizationDigest
 * @package App\Digests
 */
abstract class BaseOrganizationDigest
{
    /**
     * @var string
     */
    protected string $requiredRelation = '';
    protected string $digestKey = '';
    protected array $employeePermissions = [];

    /**
     * @param NotificationService $notificationService
     */
    public function handle(NotificationService $notificationService): void
    {
        foreach (Organization::whereHas($this->requiredRelation)->get() as $organization) {
            $this->handleOrganizationDigest($organization, $notificationService);
        }
    }

    /**
     * @param Organization $organization
     * @param NotificationService $notificationService
     * @return void
     */
    abstract protected function handleOrganizationDigest(
        Organization $organization,
        NotificationService $notificationService
    ): void;

    /**
     * @param Organization $organization
     * @return Carbon
     */
    public function getLastOrganizationDigestTime(Organization $organization): Carbon
    {
        return $organization->lastDigestOfType($this->digestKey)->created_at ?? now()->subDay();
    }

    /**
     * @param Organization $organization
     */
    protected function updateLastDigest(Organization $organization): void {
        $organization->digests()->create([
            'type' => $this->digestKey
        ]);
    }

    /**
     * @param Organization $organization
     * @param MailBodyBuilder $emailBody
     * @param NotificationService $notificationService
     */
    protected function sendOrganizationDigest(
        Organization $organization,
        MailBodyBuilder $emailBody,
        NotificationService $notificationService
    ): void {
        $employees = $organization->employeesWithPermissions($this->employeePermissions);

        foreach ($employees as $employee) {
            if ($employee->identity->email) {
                $this->sendDigest($notificationService, $employee->identity->email, $emailBody);
            }
        }

        $this->updateLastDigest($organization);
    }

    /**
     * @param NotificationService $notificationService
     * @param string $email
     * @param MailBodyBuilder $emailBody
     * @return void
     */
    protected function sendDigest(
        NotificationService $notificationService,
        string $email,
        MailBodyBuilder $emailBody
    ): void {
        $notificationService->sendDigest($email, $this->getDigestMailable($emailBody));
    }

    /**
     * @param MailBodyBuilder $emailBody
     * @return BaseDigestMail
     */
    abstract protected function getDigestMailable(
        MailBodyBuilder $emailBody
    ): BaseDigestMail;
}