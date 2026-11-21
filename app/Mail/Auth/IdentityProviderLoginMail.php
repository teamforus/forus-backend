<?php

namespace App\Mail\Auth;

use App\Helpers\Markdown;
use App\Mail\ImplementationMail;
use App\Models\Implementation;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Config;
use League\CommonMark\Exception\CommonMarkException;

class IdentityProviderLoginMail extends ImplementationMail
{
    /**
     * @param Implementation $implementation
     * @param string $clientType
     * @param bool $active
     * @param string $accountType
     */
    public function __construct(
        Implementation $implementation,
        string $clientType,
        bool $active,
        string $accountType,
    ) {
        $isDashboard = in_array($clientType, Config::get('forus.clients.dashboards'), true);
        $isEmployee = $accountType === IdentityProviderMembership::ACCOUNT_TYPE_EMPLOYEE;
        $implementation = $isDashboard ? Implementation::general() : $implementation;

        $guidance = match (true) {
            !$active => 'inactive',
            $clientType === Implementation::FRONTEND_WEBSHOP => 'webshop',
            in_array($clientType, Config::get('forus.clients.mobile'), true) => $isEmployee ? 'employee_me_app' : 'me_app',
            $isDashboard => $isEmployee ? 'employee_dashboard' : 'dashboard',
            default => 'other',
        };

        parent::__construct([
            'guidance' => $guidance,
            'frontend_url' => match ($guidance) {
                'webshop' => $implementation->urlWebshop(),
                'dashboard', 'employee_dashboard' => $implementation->urlFrontend($clientType, '/'),
                default => null,
            },
        ], $implementation->getEmailFrom());
    }

    /**
     * @throws CommonMarkException
     * @return Mailable
     */
    public function build(): Mailable
    {
        $this->subject = __('mails.identity_provider_login.subject');

        return $this->buildSystemMail('identity_provider_login');
    }

    /**
     * @param array $data
     * @throws CommonMarkException
     * @return array
     */
    protected function getMailExtraData(array $data): array
    {
        $guidanceKey = 'mails.identity_provider_login.' . $data['guidance'];

        return [
            'guidance' => Markdown::convert(__($guidanceKey . '.' . $this->communicationType)),
            'navigation_link' => $data['frontend_url']
                ? $this->makeLink($data['frontend_url'], __($guidanceKey . '.link'))
                : '',
        ];
    }
}
