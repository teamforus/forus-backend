<?php

namespace Tests\Unit\MailTests;

use App\Mail\Auth\IdentityProviderLoginMail;
use App\Models\Implementation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReflectionException;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class IdentityProviderLoginMailTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @throws ReflectionException
     * @return void
     */
    public function testWebshopGuidanceUsesRequestedWebshopAndCommunicationStyle(): void
    {
        foreach ([false, true] as $informal) {
            $implementation = $this->makeTestImplementation($this->makeTestOrganization($this->makeIdentity()), [
                'url_webshop' => 'https://requester-webshop.example.test',
                'informal_communication' => $informal,
            ]);

            $content = $this->renderGuidance($implementation, Implementation::FRONTEND_WEBSHOP);

            $this->assertSame([$implementation->urlWebshop()], $this->getEmailLinks($content));
            $this->assertStringContainsString('Log in via Microsoft', $content);
            $this->assertStringContainsString($informal ? 'je organisatie' : 'uw organisatie', $content);
            $this->assertStringContainsString($informal ? 'Je hebt geprobeerd' : 'U heeft geprobeerd', $content);
        }
    }

    /**
     * @throws ReflectionException
     * @return void
     */
    public function testMeGuidanceExplainsEnteringTheAppCodeOnTheWebshopWithoutLinks(): void
    {
        $implementation = $this->makeTestImplementation($this->makeTestOrganization($this->makeIdentity()));
        $content = $this->renderGuidance($implementation, Implementation::ME_APP_ANDROID);
        $text = html_entity_decode(strip_tags($content));

        $this->assertStringContainsString('Log in via Microsoft op de webshop', $text);
        $this->assertStringContainsString('Open in de Me-app het scherm voor inloggen vanaf een ander apparaat', $text);
        $this->assertStringContainsString("Open het gebruikersmenu in de webshop en kies 'Log in op de app'", $text);
        $this->assertStringContainsString('Vul daar de code uit de Me-app in', $text);
        $this->assertSame([], $this->getEmailLinks($content));
    }

    /**
     * @throws ReflectionException
     * @return void
     */
    public function testDashboardGuidanceUsesGeneralImplementationAndExplainsNoDashboardAccess(): void
    {
        $implementation = $this->makeTestImplementation($this->makeTestOrganization($this->makeIdentity()), [
            'url_sponsor' => 'https://unused-dashboard.example.test',
            'informal_communication' => !Implementation::general()->informal_communication,
        ]);

        $content = $this->renderGuidance($implementation, Implementation::FRONTEND_SPONSOR_DASHBOARD);

        $this->assertSame([Implementation::general()->urlSponsorDashboard()], $this->getEmailLinks($content));
        $this->assertStringContainsString('geeft geen toegang tot het dashboard', $content);

        $this->assertStringContainsString(
            Implementation::general()->informal_communication ? 'Je hebt geprobeerd' : 'U heeft geprobeerd',
            $content,
        );
    }

    /**
     * @throws ReflectionException
     * @return void
     */
    public function testInactiveGuidanceReplacesClientLoginInstructions(): void
    {
        $implementation = $this->makeTestImplementation($this->makeTestOrganization($this->makeIdentity()));

        foreach ([
            Implementation::FRONTEND_WEBSHOP,
            Implementation::ME_APP_ANDROID,
            Implementation::FRONTEND_SPONSOR_DASHBOARD,
        ] as $clientType) {
            $content = $this->renderGuidance($implementation, $clientType, active: false);

            $this->assertStringContainsString('account is niet actief', $content);
            $this->assertStringContainsString('Neem contact op met', $content);
            $this->assertStringNotContainsString('Log in via Microsoft', $content);
            $this->assertStringNotContainsString('code uit de Me-app', $content);
            $this->assertStringNotContainsString('geeft geen toegang tot het dashboard', $content);
            $this->assertSame([], $this->getEmailLinks($content));
        }
    }

    /**
     * @param Implementation $implementation
     * @param string $clientType
     * @param bool $active
     * @throws ReflectionException
     * @return string
     */
    protected function renderGuidance(Implementation $implementation, string $clientType, bool $active = true): string
    {
        $content = (new IdentityProviderLoginMail($implementation, $clientType, $active))
            ->locale('nl')->with('hideFooter', true)->render();

        $this->assertDoesNotMatchRegularExpression('/:(guidance|navigation_link|frontend_url|email_logo)\b/', $content);

        return $content;
    }
}
