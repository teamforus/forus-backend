<?php

namespace App\Mail\Auth;

use App\Helpers\Markdown;
use App\Mail\ImplementationMail;
use App\Models\Implementation;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Config;
use League\CommonMark\Exception\CommonMarkException;

class IdentityProviderLoginMail extends ImplementationMail
{
    /**
     * @param Implementation $implementation
     * @param string $clientType
     * @param bool $active
     */
    public function __construct(Implementation $implementation, string $clientType, bool $active)
    {
        $isDashboard = in_array($clientType, Config::get('forus.clients.dashboards'), true);
        $implementation = $isDashboard ? Implementation::general() : $implementation;

        $guidance = match (true) {
            !$active => 'inactive',
            $clientType === Implementation::FRONTEND_WEBSHOP => 'webshop',
            in_array($clientType, Config::get('forus.clients.mobile'), true) => 'me_app',
            $isDashboard => 'dashboard',
            default => 'other',
        };

        parent::__construct([
            'guidance' => $guidance,
            'frontend_url' => match ($guidance) {
                'webshop' => $implementation->urlWebshop(),
                'dashboard' => $implementation->urlFrontend($clientType, '/'),
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
