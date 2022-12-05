<?php

namespace App\Services\BankService\Seeders;

use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Model\Generated\Endpoint\OauthCallbackUrl;
use bunq\Model\Generated\Endpoint\OauthClient;
use bunq\Util\BunqEnumApiEnvironmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use App\Services\BankService\Models\Bank;
use Illuminate\Support\Facades\Config;
use Throwable;

/**
 * @noinspection PhpUnused
 */
class BanksTableSeeder extends Seeder
{
    public string $warningBankUsageMessage =
        "Although BUNQ/BNG connection is not required to run the app, " .
        "you will not be able to use/test some platform functionality, " .
        "like: top-ups and transactions payout which means all the " .
        "transactions will always remain as ‘pending’ unless you " .
        "change the state to 'success' manually as sponsor.";

    /**
     * @var string Bunq allows only one active oauth client
     */
    protected string $bunqContextFile = '/bank-connections/bunq/bunq-data.json';
    protected string $bngContextFile = '/bank-connections/bng/bng-data.json';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->bunqBank();
        $this->bngBank();
    }

    /**
     * @return void
     */
    protected function bngBank(): void
    {
        if ($this->hasBngContextFile()) {
            echo "Using existing BNG context file from: ./storage$this->bngContextFile.\n";

            Bank::firstOrCreate([
                'key' => 'bng',
                'name' => 'BNG',
                'transaction_cost' => 0,
            ])->update([
                'data' => json_decode(file_get_contents(storage_path($this->bngContextFile)), true),
            ]);
        } else {
            $this->printWarning("No BNG context file at: ./storage$this->bngContextFile.");
        }
    }

    /**
     * @return void
     */
    protected function bunqBank(): void
    {
        $hasBunqContextFile = $this->hasBunqContextFile();

        if ($hasBunqContextFile) {
            echo "Using existing bunq context file from: ./storage$this->bunqContextFile.\n";
            $bank = $this->useExistingBunqBankInstallation();
        } else {
            echo "Making new bunq context installation.\n";
            $bank = $this->makeBunqBankInstallation();
        }

        if ($bank && !$hasBunqContextFile || ($hasBunqContextFile && !$bank->oauth_redirect_id)) {
            $bank->useContext();

            $oauthClient = $bank->getOauthClient();
            $redirectUrl = $bank->buildOauthRedirectUrl();
            $registeredUrls = self::getBunqRedirectUrls($oauthClient->getId());
            $callbackUrlExists = in_array($redirectUrl, $registeredUrls);

            $bank->forceFill(array_merge([
                'oauth_redirect_url' => $redirectUrl,
            ], $callbackUrlExists ? [] : [
                'oauth_redirect_id' => static::registerBunqOauthCallbackUrl($oauthClient->getId(), $redirectUrl),
            ]))->update();
        }
    }

    /**
     * @param int $oauthClientId
     * @return array
     */
    protected static function getBunqRedirectUrls(int $oauthClientId): array
    {
        return array_map(function(OauthCallbackUrl $callbackUrl) {
            return $callbackUrl->getUrl();
        }, OauthCallbackUrl::listing($oauthClientId)->getValue());
    }

    /**
     * @param int $oauthClientId
     * @param string $redirectUrl
     * @return int
     */
    protected static function registerBunqOauthCallbackUrl(int $oauthClientId, string $redirectUrl): int
    {
        return OauthCallbackUrl::create($oauthClientId, $redirectUrl)->getValue();
    }

    /**
     * @return bool
     */
    public function hasBunqContextFile(): bool
    {
        return file_exists(storage_path($this->bunqContextFile));
    }

    /**
     * @return bool
     */
    public function hasBngContextFile(): bool
    {
        return file_exists(storage_path($this->bngContextFile));
    }

    /**
     * @return Model|Bank
     */
    protected function useExistingBunqBankInstallation(): Model|Bank
    {
        $key = 'bunq';
        $name = 'Bunq';
        $data = json_decode(file_get_contents(storage_path($this->bunqContextFile)), true);

        return Bank::updateOrCreate(compact('key'), array_merge(compact('name'), [
            'data' => array_only($data, ['context', 'oauth_client']),
            'transaction_cost' => .11,
            'oauth_redirect_id' => array_get($data, 'oauth_redirect_id'),
            'oauth_redirect_url' => array_get($data, 'oauth_redirect_url'),
        ]));
    }

    /**
     * @return array|null
     */
    public function makeBunqBankInstallation(): ?Bank
    {
        $key = 'bunq';
        $bunqKey = Config::get('forus.seeders.bank_seeder.bunq_key');
        $environment = $this->apiKeyToEnvironmentType($bunqKey);
        $description = 'Forus PSD2 development installation.';
        $errorPrefix = "Could not create BUNQ bank context/installation: ";

        if (!$bunqKey) {
            $this->printWarning($errorPrefix . "The api key is not present in your .env file.");
            return null;
        }

        try {
            $allPermittedIp = json_decode(Config::get('forus.seeders.bank_seeder.bunq_ip', "[]"), true);
            $context = ApiContext::create($environment, $bunqKey, $description, $allPermittedIp);

            BunqContext::loadApiContext(ApiContext::fromJson($context->toJson()));

            $oauth_client = OauthClient::listing()->getValue()[0] ?? null;
            $oauth_client = $oauth_client ?: OauthClient::get(OauthClient::create()->getValue())->getValue();

            return Bank::updateOrCreate(compact('key'), [
                'name' => 'Bunq',
                'transaction_cost' => .11,
                'data->context' => json_decode($context->toJson()),
                'data->oauth_client' => $oauth_client,
            ]);
        } catch (Throwable $e) {
            $error = "[Error] - " . $e->getMessage();
            $message = "Error while making bank context using the key from the .env file.";

            $this->printWarning("$errorPrefix$message\n$error");
            return null;
        }
    }

    /**
     * @param string|null $apiKey
     * @return BunqEnumApiEnvironmentType
     */
    public function apiKeyToEnvironmentType(?string $apiKey): BunqEnumApiEnvironmentType
    {
        if (is_string($apiKey) && !starts_with(strtolower($apiKey), 'sandbox')) {
            return BunqEnumApiEnvironmentType::PRODUCTION();
        }

        return BunqEnumApiEnvironmentType::SANDBOX();
    }

    /**
     * @param string $message
     */
    public function printWarning(string $message): void
    {
        echo $this->makeHeader("WARNING");
        echo $this->makeText($message);
        echo $this->makeHeader("INFO");
        echo $this->makeText($this->warningBankUsageMessage);
        echo $this->makeHeader("END");
    }

    /**
     * @param string $header
     * @param int $length
     * @return string
     */
    public function makeHeader(string $header, int $length = 80): string
    {
        $size = ($length - strlen($header) - 4) / 2;
        $start = str_repeat('=', ceil($size));
        $end = str_repeat('=', floor($size));

        return sprintf("%s [%s] %s\n", $start, $header, $end);
    }

    /**
     * @param string $text
     * @param int $length
     * @param int $offset
     * @return string
     */
    public function makeText(string $text, int $length = 80, int $offset = 4): string
    {
        return implode("\n", array_map(function($row) use ($offset) {
            return str_repeat(" ", $offset) . $row;
        }, explode("\n", wordwrap($text, $length)))) . "\n";
    }
}
