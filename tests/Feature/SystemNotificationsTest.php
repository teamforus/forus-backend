<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\IdentityProxy;
use App\Models\Implementation;
use App\Models\Notification;
use App\Models\SystemNotification;
use App\Models\Voucher;
use App\Services\MediaService\Traits\UsesMediaService;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SystemNotificationsTest extends TestCase
{
    use UsesMediaService, DatabaseTransactions, WithFaker;

    protected array $templateStructure = [
        'id', 'content', 'formal', 'fund_id', 'implementation_id', 'key', 'title', 'type',
    ];

    protected array $systemNotificationStructure = [
        'editable', 'enable_all', 'enable_database', 'enable_mail', 'enable_push', 'group', 'key', 'optional', 'order',
        'channels' => [],
    ];

    /**
     * @return void
     */
    public function testCustomMailNotificationTemplateFormal()
    {
        $implementation = Implementation::byKey('nijmegen');
        $notifications = $this->getCleanSystemNotifications([
            'notifications_identities.identity_voucher_assigned_budget',
        ]);

        $implementation->update([
            'informal_communication' => false,
        ]);

        foreach ($notifications as $notification) {
            $this->startCustomTemplate($implementation, $notification, 'mail');
        }
    }

    /**
     * @return void
     */
    public function testCustomDatabaseNotificationTemplateFormal()
    {
        $implementation = Implementation::byKey('nijmegen');
        $notifications = $this->getCleanSystemNotifications([
            'notifications_identities.identity_voucher_assigned_budget',
        ]);

        $implementation->update([
            'informal_communication' => false,
        ]);

        foreach ($notifications as $notification) {
            $this->startCustomTemplate($implementation, $notification, 'database');
        }
    }

    /**
     * @return void
     */
    public function testCustomMailNotificationTemplateInformal()
    {
        $implementation = Implementation::byKey('nijmegen');
        $notifications = $this->getCleanSystemNotifications([
            'notifications_identities.identity_voucher_assigned_budget',
        ]);

        $implementation->update([
            'informal_communication' => true,
        ]);

        foreach ($notifications as $notification) {
            $this->startCustomTemplate($implementation, $notification, 'mail');
        }
    }

    /**
     * @return void
     */
    public function testCustomDatabaseNotificationTemplateInformal()
    {
        $implementation = Implementation::byKey('nijmegen');
        $notifications = $this->getCleanSystemNotifications([
            'notifications_identities.identity_voucher_assigned_budget',
        ]);

        $implementation->update([
            'informal_communication' => true,
        ]);

        foreach ($notifications as $notification) {
            $this->startCustomTemplate($implementation, $notification, 'database');
        }
    }

    /**
     * @param Implementation $implementation
     * @param SystemNotification $notification
     * @param string $channel
     * @return void
     */
    protected function startCustomTemplate(
        Implementation $implementation,
        SystemNotification $notification,
        string $channel,
    ): void {
        // Test default email template
        $funds = $this->getFunds($implementation);
        $response = $this->fetchCustomTemplate($implementation, $notification);
        $this->assertSystemNotificationTemplates($response, 3, 0);
        $templateDefault = collect($response->json('data.templates_default'))->where('type', $channel)->first();

        // Assert default email template is being used on implementation level
        foreach ($funds as $fund) {
            $this->assertNotifications($fund, $this->replaceVars($templateDefault['title'], $fund));
        }

        // Test custom implementation level template
        $this->setCustomTemplate($implementation, $notification, [
            'templates' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => null,
                'title'=> 'Lorem :fund_name',
                'content' => 'Ipsum',
                'type' => $channel,
            ], [
                'formal' => $implementation->informal_communication,
                'fund_id' => null,
                'title'=> 'Value that should not be ignored',
                'content' => 'Value that should not be ignored',
                'type' => $channel,
            ]]
        ]);

        $response = $this->fetchCustomTemplate($implementation, $notification);
        $this->assertSystemNotificationTemplates($response, 3, 1);

        // Assert custom email template is being used on implementation level
        foreach ($funds as $fund) {
            $this->assertNotifications($fund, $this->replaceVars('Lorem :fund_name', $fund), $channel);
        }

        // Test custom implementation level template
        $this->setCustomTemplate($implementation, $notification, [
            'templates' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => $funds[0]->id,
                'title'=> 'Lorem II :fund_name',
                'content' => 'Ipsum',
                'type' => $channel,
            ], [
                'formal' => $implementation->informal_communication,
                'fund_id' => $funds[0]->id,
                'title'=> 'Value that should not be ignored',
                'content' => 'Value that should not be ignored',
                'type' => $channel,
            ]]
        ]);

        $response = $this->fetchCustomTemplate($implementation, $notification);
        $this->assertSystemNotificationTemplates($response, 3, 2);

        // Assert custom email template is being used on implementation level
        foreach ($funds as $index => $fund) {
            $title = $this->replaceVars($index == 0 ? 'Lorem II :fund_name' : 'Lorem :fund_name', $fund);
            $this->assertNotifications($fund, $title, $channel);
        }

        // Test resetting custom template
        $this->setCustomTemplate($implementation, $notification, [
            'templates_remove' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => $funds[0]->id,
                'type' => $channel,
            ]]
        ]);

        $response = $this->fetchCustomTemplate($implementation, $notification);
        $this->assertSystemNotificationTemplates($response, 3, 1);

        // Assert custom email template is being used on implementation level
        foreach ($funds as $fund) {
            $this->assertNotifications($fund, $this->replaceVars('Lorem :fund_name', $fund), $channel);
        }

        // Test resetting implementation custom template
        $this->setCustomTemplate($implementation, $notification, [
            'templates' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => $funds[1]->id,
                'title'=> 'Lorem III :fund_name',
                'content' => 'Ipsum',
                'type' => $channel,
            ], [
                'formal' => $implementation->informal_communication,
                'fund_id' => $funds[1]->id,
                'title'=> 'Value that should not be ignored',
                'content' => 'Value that should not be ignored',
                'type' => $channel,
            ]],
            'templates_remove' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => null,
                'type' => $channel,
            ]]
        ]);

        $response = $this->fetchCustomTemplate($implementation, $notification);
        $this->assertSystemNotificationTemplates($response, 3, 1);

        // Assert custom email template is being used on implementation level
        foreach ($funds as $index => $fund) {
            $title = $this->replaceVars($index == 0 ? $templateDefault['title'] : 'Lorem III :fund_name', $fund);
            $this->assertNotifications($fund, $title, $channel);
        }
    }

    /**
     * @param TestResponse $response
     * @param int $templatesDefaultCount
     * @param int $templatesCount
     * @return void
     */
    protected function assertSystemNotificationTemplates(
        TestResponse $response,
        int $templatesDefaultCount,
        int $templatesCount,
    ): void {
        $responseData = $response->json('data');

        self::assertCount(
            $templatesCount,
            $responseData['templates'],
            "There should be $templatesCount custom templates",
        );

        self::assertCount(
            $templatesDefaultCount,
            $responseData['templates_default'],
            "There should be $templatesDefaultCount default templates.",
        );
    }

    /**
     * @param Implementation $implementation
     * @param SystemNotification $notification
     * @param array $data
     * @return TestResponse
     */
    protected function setCustomTemplate(
        Implementation $implementation,
        SystemNotification $notification,
        array $data,
    ): TestResponse {
        $response = $this->patchJson(sprintf(
            '/api/v1/platform/organizations/%s/implementations/%s/system-notifications/%s',
            $implementation->organization_id,
            $implementation->id,
            $notification->id,
        ), $data, $this->makeApiHeaders($this->getEmployeeProxy($implementation)));

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => array_merge($this->systemNotificationStructure, [
                'templates' => [],
                'templates_default' => [$this->templateStructure],
            ])
        ]);

        return $response;
    }

    /**
     * @param Implementation $implementation
     * @param SystemNotification $notification
     * @return TestResponse
     */
    protected function fetchCustomTemplate(
        Implementation $implementation,
        SystemNotification $notification
    ): TestResponse {
        $response = $this->getJson(sprintf(
            '/api/v1/platform/organizations/%s/implementations/%s/system-notifications/%s',
            $implementation->organization_id,
            $implementation->id,
            $notification->id,
        ), $this->makeApiHeaders($this->getEmployeeProxy($implementation)));

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => array_merge($this->systemNotificationStructure, [
                'templates' => [],
                'templates_default' => [$this->templateStructure],
            ])
        ]);

        return $response;
    }

    /**
     * @param string $content
     * @param Fund $fund
     * @return string
     */
    protected function replaceVars(string $content, Fund $fund): string
    {
        return str_var_replace($content, [
            'fund_name' => $fund->name,
        ]);
    }

    /**
     * @param array $keys
     * @return Collection|SystemNotification
     */
    protected function getCleanSystemNotifications(array $keys): Collection|Arrayable
    {
        $notifications = SystemNotification::whereIn('key', $keys)->get();

        $notifications->each(function(SystemNotification $notification) {
            $notification->templates()
                ->whereRelation('implementation', 'key', '!=', Implementation::KEY_GENERAL)
                ->delete();
        });

        return $notifications;
    }

    /**
     * @param Fund $fund
     * @return Voucher
     */
    protected function makeVoucher(Fund $fund): Voucher
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        return $fund->makeVoucher($identity->address, [], 100);
    }

    /**
     * @param Implementation $implementation
     * @return IdentityProxy
     */
    protected function getEmployeeProxy(Implementation $implementation): IdentityProxy
    {
        return $this->makeIdentityProxy($implementation->organization->identity);
    }

    /**
     * @param Implementation $implementation
     * @return Collection|Fund[]
     */
    protected function getFunds(Implementation $implementation): Collection|Arrayable
    {
        return $implementation->funds;
    }

    /**
     * @param Fund $fund
     * @param string $subject
     * @param string $channel
     * @return void
     */
    private function assertNotifications(
        Fund $fund,
        string $subject = '',
        string $channel = '',
    ): void {
        if ($channel == 'mail') {
            $this->assertMailNotification($fund, $subject);
        }

        if ($channel == 'database') {
            $this->assertDatabaseNotfication($fund, $subject);
        }
    }

    /**
     * @param Fund $fund
     * @param string $subject
     * @return void
     */
    private function assertMailNotification(
        Fund $fund,
        string $subject = '',
    ): void {
        $now = now();
        $voucher = $this->makeVoucher($fund);

        $this->assertTrue(
            $this->getFirstEmailsBySubjectQuery($voucher->identity->email, $subject, $now)->exists(),
            'Expected to send an email with "' . $subject . '" as subject.'
        );
    }

    /**
     * @param Fund $fund
     * @param string $subject
     * @return void
     */
    private function assertDatabaseNotfication(
        Fund $fund,
        string $subject = '',
    ): void {
        $now = now();
        $voucher = $this->makeVoucher($fund);
        $notificationIds = Notification::where('created_at', '>=', $now)->pluck('id');

        $headers = $this->makeApiHeaders($this->makeIdentityProxy($voucher->identity), [
            'Client-Type' => 'webshop',
            'Client-Key' => $fund->fund_config->implementation->key,
        ]);

        $notifications = $this->getJson('/api/v1/platform/notifications?per_page=10', $headers);
        $notifications->assertSuccessful();

        $notifications = array_filter($notifications->json('data'), function(array $notification) use (
            $now, $notificationIds
        ) {
            return
                $notificationIds->search($notification['id']) !== false &&
                $notification['type'] == 'notifications_identities.identity_voucher_assigned_budget';
        });

        self::assertCount(1, $notifications, 'Only 1 database notification expected.');
        $this->assertStringContainsString($subject, array_first($notifications)['title']);
    }
}
