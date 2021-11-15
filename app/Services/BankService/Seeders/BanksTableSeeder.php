<?php

namespace App\Services\BankService\Seeders;

use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Model\Generated\Endpoint\OauthCallbackUrl;
use bunq\Model\Generated\Endpoint\OauthClient;
use bunq\Util\BunqEnumApiEnvironmentType;
use Illuminate\Database\Seeder;
use App\Services\BankService\Models\Bank;
use Exception;

/**
 * @noinspection PhpUnused
 */
class BanksTableSeeder extends Seeder
{
    public $warningBankUsageMessage =
        "Although BUNQ/BNG connection is not required to run the app, " .
        "you will not be able to use/test some platform functionality, " .
        "like: top-ups and transactions payout which means all the " .
        "transactions will always remain as ‘pending’ unless you " .
        "change the state to 'success' manually as sponsor.";

    /**
     * @var string Bunq allows only one active oauth client
     */
    protected $bunqContextFile = '/bank-contexts/bunq-data.json';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $hasBunqContextFile = $this->hasBunqContextFile();

        if ($hasBunqContextFile) {
            echo "Using existing bank context file from: ./storage$this->bunqContextFile.\n";
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

            if ($callbackUrlExists) {
                echo "exists!\n";
            } else {
                echo "create!\n";
            }

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
     * @return Bank|\Illuminate\Database\Eloquent\Model
     */
    protected function useExistingBunqBankInstallation()
    {
        $key = 'bunq';
        $name = 'Bunq';
        $data = json_decode(file_get_contents(storage_path($this->bunqContextFile)));

        return Bank::updateOrCreate(compact('key'), array_merge(compact('name'), [
            'data' => array_only($data, ['context', 'oauth_client']),
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
        $bunqKey = env('DB_SEED_BUNQ_KEY');
        $environment = BunqEnumApiEnvironmentType::SANDBOX();
        $description = 'Forus PSD2 development installation.';
        $errorPrefix = "Could not create BUNQ bank context/installation: ";

        if (!env('DB_SEED_BUNQ_KEY')) {
            $this->printWarning($errorPrefix . "The api key is not present in your .env file.");
            return null;
        }

        try {
            $allPermittedIp = json_decode(env('DB_SEED_BUNQ_IP', "[]"), true);
            $context = ApiContext::create($environment, $bunqKey, $description, $allPermittedIp);

            BunqContext::loadApiContext(ApiContext::fromJson($context->toJson()));

            $oauth_client = OauthClient::listing()->getValue()[0] ?? null;
            $oauth_client = $oauth_client ?: OauthClient::get(OauthClient::create()->getValue())->getValue();

            return Bank::updateOrCreate(compact('key'), [
                'name'                  => 'Bunq',
                'data->context'         => json_decode($context->toJson()),
                'data->oauth_client'    => $oauth_client,
            ]);
        } catch (Exception $exception) {
            $error = "[Error] - " . $exception->getMessage();
            $message = "Error while making bank context using the key from the .env file.";

            $this->printWarning("$errorPrefix$message\n$error");
            return null;
        }
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
    public function makeHeader(string $header, $length = 80): string
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
    public function makeText(string $text, $length = 80, $offset = 4): string
    {
        return implode("\n", array_map(function($row) use ($offset) {
            return str_repeat(" ", $offset) . $row;
        }, explode("\n", wordwrap($text, $length)))) . "\n";
    }
}
