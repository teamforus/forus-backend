<?php

namespace Tests\Feature;

use App\Helpers\Arr;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Notification;
use App\Models\SystemNotification;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;

class SystemNotificationsTest extends TestCase
{
    use WithFaker;
    use MakesTestVouchers;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use DatabaseTransactions;

    protected array $templateStructure = [
        'id', 'content', 'formal', 'fund_id', 'implementation_id', 'key', 'title', 'type',
    ];

    protected array $systemNotificationStructure = [
        'editable', 'enable_all', 'enable_database', 'enable_mail', 'enable_push', 'group', 'key',
        'optional', 'order', 'funds',
        'channels' => [],
    ];

    /**
     * @return void
     */
    public function testCustomMailNotificationTemplateFormal()
    {
        $this->startCustomTemplate(
            $this->makeTestNotificationImplementation(),
            SystemNotification::firstWhere('key', 'notifications_identities.identity_voucher_assigned_budget'),
            'mail',
        );
    }

    /**
     * @return void
     */
    public function testCustomDatabaseNotificationTemplateFormal()
    {
        $this->startCustomTemplate(
            $this->makeTestNotificationImplementation(),
            SystemNotification::firstWhere('key', 'notifications_identities.identity_voucher_assigned_budget'),
            'database',
        );
    }

    /**
     * @return void
     */
    public function testCustomMailNotificationTemplateInformal()
    {
        $this->startCustomTemplate(
            $this->makeTestNotificationImplementation(true),
            SystemNotification::firstWhere('key', 'notifications_identities.identity_voucher_assigned_budget'),
            'mail',
        );
    }

    /**
     * @return void
     */
    public function testCustomDatabaseNotificationTemplateInformal()
    {
        $this->startCustomTemplate(
            $this->makeTestNotificationImplementation(true),
            SystemNotification::firstWhere('key', 'notifications_identities.identity_voucher_assigned_budget'),
            'database',
        );
    }

    /**
     * @return void
     */
    public function testFundScopedNotificationConfigOverridesSelectedFundOnly()
    {
        $implementation = $this->makeTestNotificationImplementation();
        $notification = SystemNotification::firstWhere('key', 'notifications_identities.identity_voucher_assigned_budget');
        $funds = $implementation->funds->take(2)->values();

        $notification->system_notification_configs()
            ->where('implementation_id', $implementation->id)
            ->delete();

        $this->assertCount(2, $funds);

        $this->updateNotification($implementation, $notification, [
            'enable_database' => false,
        ]);

        $this->updateNotification($implementation, $notification, [
            'enable_database' => true,
            'enable_mail' => false,
        ], $funds[0]->id);

        $notification = $notification->fresh([
            'system_notification_configs',
        ]);

        $this->assertFalse($notification->getConfig($implementation, $funds[0]->id)?->enable_mail ?? true);
        $this->assertTrue($notification->getConfig($implementation, $funds[0]->id)?->enable_database ?? false);

        $response = $this->fetchCustomTemplate($implementation, $notification);
        $fundStates = collect($response->json('data.funds'))->keyBy('id');

        $this->assertTrue($response->json('data.enable_mail'));
        $this->assertFalse($response->json('data.enable_database'));
        $this->assertTrue($fundStates->has($funds[0]->id));
        $this->assertTrue($fundStates->has($funds[1]->id));
        $this->assertSame($funds[0]->name, $fundStates[$funds[0]->id]['name']);
        $this->assertFalse($fundStates[$funds[0]->id]['enable_mail']);
        $this->assertTrue($fundStates[$funds[0]->id]['enable_database']);
        $this->assertTrue($fundStates[$funds[1]->id]['enable_mail']);
        $this->assertTrue($fundStates[$funds[1]->id]['enable_database']);
        $this->assertSame(['mail', 'push'], array_values($notification->channels($implementation)));
        $this->assertSame(['push'], array_values($notification->channels($implementation, $funds[0]->id)));
        $this->assertSame(['mail', 'push'], array_values($notification->channels($implementation, $funds[1]->id)));
    }

    /**
     * @return void
     */
    public function testEditableNotificationUsesFundSpecificTemplateContext()
    {
        $implementation = $this->makeTestNotificationImplementation();
        $notification = SystemNotification::firstWhere('key', 'notifications_identities.fund_request_created');
        $fund = $implementation->funds->first();

        $this->setCustomTemplate($implementation, $notification, [
            'templates' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => $fund->id,
                'title' => 'Fund scoped :fund_name',
                'content' => 'Ipsum',
                'type' => 'mail',
            ]],
        ], $fund->id);

        $notification = $notification->fresh('templates');
        $implementationTemplate = $notification->templates
            ->where('implementation_id', $implementation->id)
            ->whereNull('fund_id')
            ->where('formal', !$implementation->informal_communication)
            ->where('type', 'mail')
            ->first();

        $this->assertSame(1, $notification->templates->where('fund_id', $fund->id)->count());
        $this->assertNull($implementationTemplate);
        $this->assertSame('Fund scoped :fund_name', $notification->findTemplate($implementation, $fund->id, 'mail')?->title);
        $this->assertNotSame('Fund scoped :fund_name', $notification->findTemplate($implementation, null, 'mail')?->title);
    }

    /**
     * @return void
     */
    public function testNonOptionalNotificationUsesFundSpecificTemplateButIgnoresConfig()
    {
        $implementation = $this->makeTestNotificationImplementation();
        $notification = SystemNotification::firstWhere('key', 'notifications_identities.fund_request_created');
        $fund = $implementation->funds->first();

        $this->assertFalse($notification->optional);

        $this->setCustomTemplate($implementation, $notification, [
            'templates' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => $fund->id,
                'title' => 'Scoped :fund_name',
                'content' => 'Ipsum',
                'type' => 'mail',
            ]],
        ], $fund->id);

        $this->updateNotification($implementation, $notification, [
            'enable_mail' => false,
        ], $fund->id);

        $notification = $notification->fresh([
            'templates',
            'system_notification_configs',
        ]);

        $configsCount = $notification->system_notification_configs
            ->where('implementation_id', $implementation->id)
            ->where('fund_id', $fund->id)
            ->count();

        $this->assertSame(0, $configsCount);
        $this->assertSame(['database', 'mail'], array_values($notification->channels($implementation, $fund->id)));
        $this->assertSame('Scoped :fund_name', $notification->findTemplate($implementation, $fund->id, 'mail')?->title);
        $this->assertNotSame('Scoped :fund_name', $notification->findTemplate($implementation, null, 'mail')?->title);
    }

    /**
     * @return void
     */
    public function testFundTemplateResetOnNonOptionalNotification()
    {
        $implementation = $this->makeTestNotificationImplementation();
        $notification = SystemNotification::firstWhere('key', 'notifications_identities.fund_request_created');
        $fund = $implementation->funds->first();

        $this->setCustomTemplate($implementation, $notification, [
            'templates' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => null,
                'title' => 'Implementation :fund_name',
                'content' => 'Ipsum',
                'type' => 'mail',
            ]],
        ]);

        $this->setCustomTemplate($implementation, $notification, [
            'templates' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => $fund->id,
                'title' => 'Fund :fund_name',
                'content' => 'Ipsum',
                'type' => 'mail',
            ]],
        ], $fund->id);

        $this->setCustomTemplate($implementation, $notification, [
            'templates_remove' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => $fund->id,
                'type' => 'mail',
            ]],
        ], $fund->id);

        $notification = $notification->fresh('templates');

        $this->assertSame(
            0,
            $notification->templates
                ->where('implementation_id', $implementation->id)
                ->where('fund_id', $fund->id)
                ->where('type', 'mail')
                ->count(),
        );
        $this->assertSame('Implementation :fund_name', $notification->findTemplate($implementation, null, 'mail')?->title);
        $this->assertSame('Implementation :fund_name', $notification->findTemplate($implementation, $fund->id, 'mail')?->title);
    }

    /**
     * @return void
     */
    public function testImplementationLevelUpdateDoesNotModifyFundScopedConfigOrTemplates()
    {
        $implementation = $this->makeTestNotificationImplementation();
        $notification = SystemNotification::firstWhere('key', 'notifications_identities.identity_voucher_assigned_budget');
        $fund = $implementation->funds->first();

        $this->updateNotification($implementation, $notification, [
            'enable_database' => false,
        ]);

        $this->updateNotification($implementation, $notification, [
            'enable_mail' => false,
        ], $fund->id);

        $this->setCustomTemplate($implementation, $notification, [
            'templates' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => null,
                'title' => 'Implementation :fund_name',
                'content' => 'Ipsum',
                'type' => 'mail',
            ]],
        ]);

        $this->setCustomTemplate($implementation, $notification, [
            'templates' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => $fund->id,
                'title' => 'Fund :fund_name',
                'content' => 'Ipsum',
                'type' => 'mail',
            ]],
        ], $fund->id);

        $this->setCustomTemplate($implementation, $notification, [
            'enable_push' => false,
            'templates' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => null,
                'title' => 'Implementation updated :fund_name',
                'content' => 'Ipsum',
                'type' => 'mail',
            ]],
        ]);

        $notification = $notification->fresh([
            'templates',
            'system_notification_configs',
        ]);

        $this->assertFalse($notification->getConfig($implementation)?->enable_database ?? true);
        $this->assertFalse($notification->getConfig($implementation)?->enable_push ?? true);
        $this->assertFalse($notification->getConfig($implementation, $fund->id)?->enable_mail ?? true);

        $this->assertSame(
            'Implementation updated :fund_name',
            $notification->findTemplate($implementation, null, 'mail')?->title,
        );

        $this->assertSame(
            'Fund :fund_name',
            $notification->findTemplate($implementation, $fund->id, 'mail')?->title,
        );
    }

    /**
     * @return void
     */
    public function testShowIncludesFundsForNonOptionalNotification()
    {
        $implementation = $this->makeTestNotificationImplementation();
        $notification = SystemNotification::firstWhere('key', 'notifications_identities.fund_request_created');

        $response = $this->fetchCustomTemplate($implementation, $notification);
        $funds = collect($response->json('data.funds'));

        $this->assertCount(2, $funds);
        $this->assertTrue($response->json('data.enable_all'));
        $this->assertTrue($response->json('data.enable_mail'));
        $this->assertTrue($response->json('data.enable_database'));
        $this->assertTrue($funds->every(fn (array $fund) => $fund['enable_all']));
        $this->assertTrue($funds->every(fn (array $fund) => $fund['enable_mail']));
        $this->assertTrue($funds->every(fn (array $fund) => $fund['enable_push']));
        $this->assertTrue($funds->every(fn (array $fund) => $fund['enable_database']));
    }

    /**
     * @return void
     */
    public function testMissingFundIdFallsBackToImplementationScope()
    {
        $implementation = $this->makeTestNotificationImplementation();
        $notification = SystemNotification::firstWhere('key', 'notifications_identities.identity_voucher_assigned_budget');
        $fund = $implementation->funds->first();

        $this->updateNotification($implementation, $notification, [
            'enable_mail' => false,
        ]);

        $this->updateNotification($implementation, $notification, [
            'enable_database' => false,
        ], $fund->id);

        $this->setCustomTemplate($implementation, $notification, [
            'templates' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => null,
                'title' => 'Implementation :fund_name',
                'content' => 'Ipsum',
                'type' => 'mail',
            ]],
        ]);

        $this->setCustomTemplate($implementation, $notification, [
            'templates' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => $fund->id,
                'title' => 'Fund :fund_name',
                'content' => 'Ipsum',
                'type' => 'mail',
            ]],
        ], $fund->id);

        $notification = $notification->fresh([
            'templates',
            'system_notification_configs',
        ]);

        $this->assertSame(['database', 'push'], array_values($notification->channels($implementation)));
        $this->assertSame(['push'], array_values($notification->channels($implementation, $fund->id)));
        $this->assertSame('Implementation :fund_name', $notification->findTemplate($implementation, null, 'mail')?->title);
        $this->assertSame('Fund :fund_name', $notification->findTemplate($implementation, $fund->id, 'mail')?->title);
    }

    /**
     * @return void
     */
    public function testUpdateNormalizesDuplicateImplementationScopeConfigs()
    {
        $implementation = $this->makeTestNotificationImplementation();
        $notification = SystemNotification::firstWhere('key', 'notifications_identities.identity_voucher_assigned_budget');

        $notification->system_notification_configs()
            ->where('implementation_id', $implementation->id)
            ->delete();

        foreach (range(1, 2) as $index) {
            $notification->system_notification_configs()->create([
                'implementation_id' => $implementation->id,
                'fund_id' => null,
                'enable_all' => true,
                'enable_mail' => $index === 1,
                'enable_push' => true,
                'enable_database' => true,
            ]);
        }

        $this->updateNotification($implementation, $notification, [
            'enable_mail' => false,
        ]);

        $notification = $notification->fresh('system_notification_configs');
        $configs = $notification->system_notification_configs
            ->where('implementation_id', $implementation->id)
            ->whereNull('fund_id');

        $this->assertCount(1, $configs);
        $this->assertFalse($configs->first()->enable_mail);
    }

    /**
     * @return void
     */
    public function testShowIncludesLastSentMetadataWhileIndexDoesNot()
    {
        $implementation = $this->makeTestNotificationImplementation();
        $notification = SystemNotification::firstWhere('key', 'notifications_identities.voucher_expire_soon_budget');
        $funds = $implementation->funds->take(2)->values();

        $date1 = now()->subDays(2)->startOfDay();
        $date2 = now()->subDay()->startOfDay();

        $this->seedLastSentData($notification, $funds[0], $date1);
        $this->seedLastSentData($notification, $funds[1], $date2);

        $showResponse = $this->fetchCustomTemplate($implementation, $notification);
        $showFunds = collect($showResponse->json('data.funds'))->keyBy('id');

        $this->assertSame($date2->format('Y-m-d'), $showResponse->json('data.last_sent_date'));
        $this->assertSame($date1->format('Y-m-d'), $showFunds[$funds[0]->id]['last_sent_date']);
        $this->assertSame($date2->format('Y-m-d'), $showFunds[$funds[1]->id]['last_sent_date']);

        $listResponse = $this->fetchNotificationsList($implementation);
        $indexNotification = collect($listResponse->json('data'))->where('key', $notification->key)->first();

        $this->assertIsArray($indexNotification['funds']);
        $this->assertArrayNotHasKey('last_sent_date', $indexNotification);
        $this->assertArrayNotHasKey('last_sent_date', $indexNotification['funds'][0]);
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
        $funds = $implementation->funds->take(2);
        $response = $this->fetchCustomTemplate($implementation, $notification);
        $this->assertSystemNotificationTemplates($response, 3, 0);
        $templateDefault = collect($response->json('data.templates_default'))->where('type', $channel)->first();
        $this->assertEquals(2, $funds->count(), 'Expected to find 2 funds while found ' . $funds->count() . '.');

        // Assert default email template is being used on implementation level
        foreach ($funds as $fund) {
            $this->assertNotifications($fund, $this->replaceVars($templateDefault['title'], $fund), $channel);
        }

        // Test custom implementation level template
        $this->setCustomTemplate($implementation, $notification, [
            'templates' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => null,
                'title' => 'Lorem :fund_name',
                'content' => 'Ipsum',
                'type' => $channel,
            ], [
                'formal' => $implementation->informal_communication,
                'fund_id' => null,
                'title' => 'Value that should not be ignored',
                'content' => 'Value that should not be ignored',
                'type' => $channel,
            ]],
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
                'title' => 'Lorem II :fund_name',
                'content' => 'Ipsum',
                'type' => $channel,
            ], [
                'formal' => $implementation->informal_communication,
                'fund_id' => $funds[0]->id,
                'title' => 'Value that should not be ignored',
                'content' => 'Value that should not be ignored',
                'type' => $channel,
            ]],
        ], $funds[0]->id);

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
            ]],
        ], $funds[0]->id);

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
                'title' => 'Lorem III :fund_name',
                'content' => 'Ipsum',
                'type' => $channel,
            ], [
                'formal' => $implementation->informal_communication,
                'fund_id' => $funds[1]->id,
                'title' => 'Value that should not be ignored',
                'content' => 'Value that should not be ignored',
                'type' => $channel,
            ]],
        ], $funds[1]->id);

        $this->setCustomTemplate($implementation, $notification, [
            'templates_remove' => [[
                'formal' => !$implementation->informal_communication,
                'fund_id' => null,
                'type' => $channel,
            ]],
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

        $this->assertCount(
            $templatesCount,
            $responseData['templates'],
            "There should be $templatesCount custom templates",
        );

        $this->assertCount(
            $templatesDefaultCount,
            $responseData['templates_default'],
            "There should be $templatesDefaultCount default templates.",
        );
    }

    /**
     * @param Implementation $implementation
     * @param SystemNotification $notification
     * @param array $data
     * @param int|null $fundId
     * @return TestResponse
     */
    protected function setCustomTemplate(
        Implementation $implementation,
        SystemNotification $notification,
        array $data,
        ?int $fundId = null,
    ): TestResponse {
        $response = $this->patchJson(sprintf(
            '/api/v1/platform/organizations/%s/implementations/%s/system-notifications/%s',
            $implementation->organization_id,
            $implementation->id,
            $notification->id,
        ), [
            ...$data,
            ...$fundId ? ['fund_id' => $fundId] : [],
        ], $this->makeApiHeaders($this->makeIdentityProxy($implementation->organization->identity)));

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => array_merge($this->systemNotificationStructure, [
                'templates' => [],
                'templates_default' => [$this->templateStructure],
            ]),
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
        SystemNotification $notification,
    ): TestResponse {
        $response = $this->getJson(sprintf(
            '/api/v1/platform/organizations/%s/implementations/%s/system-notifications/%s',
            $implementation->organization_id,
            $implementation->id,
            $notification->id,
        ), $this->makeApiHeaders($this->makeIdentityProxy($implementation->organization->identity)));

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'data' => array_merge($this->systemNotificationStructure, [
                'templates' => [],
                'templates_default' => [$this->templateStructure],
            ]),
        ]);

        return $response;
    }

    /**
     * @param Implementation $implementation
     * @return TestResponse
     */
    protected function fetchNotificationsList(Implementation $implementation): TestResponse
    {
        $response = $this->getJson(sprintf(
            '/api/v1/platform/organizations/%s/implementations/%s/system-notifications',
            $implementation->organization_id,
            $implementation->id,
        ), $this->makeApiHeaders($this->makeIdentityProxy($implementation->organization->identity)));

        $response->assertSuccessful();

        return $response;
    }

    /**
     * @param Implementation $implementation
     * @param SystemNotification $notification
     * @param array $data
     * @param int|null $fundId
     * @return TestResponse
     */
    protected function updateNotification(
        Implementation $implementation,
        SystemNotification $notification,
        array $data,
        ?int $fundId = null,
    ): TestResponse {
        return $this->setCustomTemplate($implementation, $notification, $data, $fundId);
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
     * @param bool $informalCommunication
     * @return Implementation
     */
    protected function makeTestNotificationImplementation(bool $informalCommunication = false): Implementation
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = $this->makeTestImplementation($organization);

        $implementation->forceFill([
            'allow_per_fund_notification_templates' => true,
            'informal_communication' => $informalCommunication,
        ])->save();

        $this->makeTestFund($organization, implementation: $implementation);
        $this->makeTestFund($organization, implementation: $implementation);

        return $implementation->refresh();
    }

    /**
     * @param Fund $fund
     * @return Voucher
     */
    protected function makeVoucher(Fund $fund): Voucher
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        return $this->makeTestVoucher($fund, $identity, amount: 100);
    }

    /**
     * @param SystemNotification $notification
     * @param Fund $fund
     * @param \Illuminate\Support\Carbon $createdAt
     * @return void
     */
    protected function seedLastSentData(SystemNotification $notification, Fund $fund, Carbon $createdAt): void
    {
        $voucher = $this->makeVoucher($fund);

        $eventLog = EventLog::forceCreate([
            'loggable_type' => $voucher->getMorphClass(),
            'loggable_id' => $voucher->id,
            'event' => 'test',
            'identity_address' => $voucher->identity->address,
            'original' => false,
            'data' => ['fund_id' => $fund->id],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        EmailLog::forceCreate([
            'event_log_id' => $eventLog->id,
            'system_notification_key' => $notification->key,
            'from_name' => 'Test',
            'from_address' => 'test@example.test',
            'to_name' => 'Test',
            'to_address' => $voucher->identity->email,
            'subject' => 'Test',
            'content' => 'Test',
            'headers' => '{}',
            'mailable' => 'TestMail',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
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
            $this->assertDatabaseNotification($fund, $subject);
        }
    }

    /**
     * @param Fund $fund
     * @param string $subject
     * @return void
     */
    private function assertMailNotification(Fund $fund, string $subject = ''): void
    {
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
    private function assertDatabaseNotification(Fund $fund, string $subject = ''): void
    {
        $now = now();
        $voucher = $this->makeVoucher($fund);
        $notificationIds = Notification::where('created_at', '>=', $now)->pluck('id');

        $headers = $this->makeApiHeaders($this->makeIdentityProxy($voucher->identity), [
            'Client-Type' => 'webshop',
            'Client-Key' => $fund->fund_config->implementation->key,
        ]);

        $notifications = $this->getJson('/api/v1/platform/notifications?per_page=10', $headers);
        $notifications->assertSuccessful();

        $notifications = array_filter($notifications->json('data'), function (array $notification) use (
            $now,
            $notificationIds
        ) {
            return
                $notificationIds->search($notification['id']) !== false &&
                $notification['type'] == 'notifications_identities.identity_voucher_assigned_budget';
        });

        $this->assertCount(1, $notifications, 'Only 1 database notification expected.');
        $this->assertStringContainsString($subject, Arr::first($notifications)['title']);
    }
}
