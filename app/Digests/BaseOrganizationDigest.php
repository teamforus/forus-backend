<?php

namespace App\Digests;

use App\Mail\Digest\BaseDigestMail;
use App\Mail\MailBodyBuilder;
use App\Models\Employee;
use App\Models\Organization;
use App\Services\EventLogService\Traits\HasDigests;
use App\Services\Forus\Identity\Repositories\Interfaces\IIdentityRepo;
use App\Services\Forus\Notification\NotificationService;

/**
 * Class BaseOrganizationDigest
 * @property IIdentityRepo $identityRepo
 * @package App\Digests
 */
abstract class BaseOrganizationDigest
{
    /**
     * @var string
     */
    protected $requiredRelation = '';
    protected $digestKey = '';
    protected $identityRepo;
    protected $employeePermissions = [];

    /**
     * @param NotificationService $notificationService
     * @param IIdentityRepo $identityRepo
     */
    public function handle(
        NotificationService $notificationService,
        IIdentityRepo $identityRepo
    ): void {
        $this->identityRepo = $identityRepo;

        foreach (Organization::whereHas(
            $this->requiredRelation
        )->get() as $organization) {
            $this->handleOrganizationDigest($organization, $notificationService);
        }
    }

    /**
     * @param Organization $organization
     * @param NotificationService $notificationService
     * @return mixed
     */
    abstract protected function handleOrganizationDigest(
        Organization $organization,
        NotificationService $notificationService
    );

    /**
     * @param Organization|HasDigests $organization
     * @return \Carbon\Carbon|\Illuminate\Support\Carbon
     */
    public function getOrganizationDigestTime(Organization $organization) {
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
            $this->sendDigest(
                $notificationService,
                $this->identityRepo->getPrimaryEmail($employee->identity_address),
                $emailBody
            );
        }

        $this->updateLastDigest($organization);
    }

    /**
     * @param NotificationService $notificationService
     * @param string $email
     * @param MailBodyBuilder $emailBody
     * @return mixed|void
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