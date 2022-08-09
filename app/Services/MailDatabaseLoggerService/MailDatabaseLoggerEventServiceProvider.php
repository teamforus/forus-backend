<?php

namespace App\Services\MailDatabaseLoggerService;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Mail\Events\MessageSending;

class MailDatabaseLoggerEventServiceProvider extends EventServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        MessageSending::class => [
            MailDatabaseLogger::class,
        ],
    ];
}