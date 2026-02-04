<?php

namespace Tests;

use App\Traits\DoesTesting;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Dusk;
use Laravel\Dusk\TestCase as BaseTestCase;
use Tests\Traits\MakesApiRequests;

abstract class DuskTestCase extends BaseTestCase
{
    use DoesTesting;
    use MakesApiRequests;
    use CreatesApplication;

    protected ?string $testStartDateTime;

    /**
     * @var string[]
     */
    protected $defaultHeaders = [
        'Accept' => 'application/json',
        'Client-Type' => 'webshop',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->testStartDateTime = now()->format('Y-m-d H:i:s');

        Dusk::selectorHtmlAttribute(Config::get('tests.dusk_selector'));
        Browser::$waitSeconds = Config::get('tests.dusk_wait_for_time');

        if (!Config::get('tests.dusk_github_action')) {
            collect(Storage::files('dusk-downloads'))
                ->reject(fn ($file) => basename($file) === '.gitignore')
                ->each(fn ($file) => Storage::delete($file));
        }
    }

    /**
     * Prepare for Dusk test execution.
     *
     * @beforeClass
     */
    public static function prepare(): void
    {
        // Only start the built-in ChromeDriver if we're NOT targeting a remote node
        if (!static::runningInSail() && empty(Env::get('DUSK_DRIVER_URL'))) {
            static::startChromeDriver([
                '--port=9515',
            ]);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     *
     * @return \Facebook\WebDriver\Remote\RemoteWebDriver
     */
    protected function driver(): RemoteWebDriver
    {
        $seleniumUrl = Env::get('DUSK_DRIVER_URL', 'http://localhost:9515');

        $options = (new ChromeOptions())
            ->addArguments(collect([
                $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            ])->unless($this->hasHeadlessDisabled(), function ($items) {
                return $items->merge([
                    '--disable-gpu',
                    '--headless',
                    '--disable-images',
                    '--disable-dev-shm-usage',
                    '--disable-background-timer-throttling',
                    '--disable-backgrounding-occluded-windows',
                    '--disable-renderer-backgrounding',
                ]);
            })->all());

        if (!Config::get('tests.dusk_github_action')) {
            $options->setExperimentalOption('prefs', [
                'download.default_directory' => Storage::path('dusk-downloads'),
            ]);
        }

        return RemoteWebDriver::create(
            $seleniumUrl,
            DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options)
        );
    }

    /**
     * Determine whether the Dusk command has disabled headless mode.
     *
     * @return bool
     */
    protected function hasHeadlessDisabled(): bool
    {
        return isset($_SERVER['DUSK_HEADLESS_DISABLED']) ||
               isset($_ENV['DUSK_HEADLESS_DISABLED']);
    }

    /**
     * Determine if the browser window should start maximized.
     *
     * @return bool
     */
    protected function shouldStartMaximized(): bool
    {
        return isset($_SERVER['DUSK_START_MAXIMIZED']) ||
               isset($_ENV['DUSK_START_MAXIMIZED']);
    }
}
