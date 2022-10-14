<?php

namespace App\Services\Forus\Session\Commands;

use App\Console\Commands\BaseCommand;
use App\Models\IdentityProxy;
use App\Models\Implementation;
use App\Services\Forus\Session\Models\Session;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;

class UpdateSessionsExpirationCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth_sessions:update-expiration
                                {--chunk-size= : chunk size.}
                                {--progress}
                                {--dry-run}
                                {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invalidate expired sessions access tokens';

    private int $chunkSize;
    private bool $progress;
    private bool $dryRun;

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Throwable
     */
    public function handle(): int
    {
        $this->chunkSize = intval($this->option('chunk-size') ?: 1_000);
        $this->progress = (bool) $this->option('progress');
        $this->dryRun = (bool) $this->option('dry-run');

        $verbose = (bool) $this->option('verbose');
        $force = (bool) $this->option('force');

        $query = $this->getIdentityProxiesQuery();
        $total = (clone $query)->count();
        $conformationMessage = "$total tokens with expired sessions found, are sure you want to continue?";

        if (!$this->dryRun && !$force && !$this->confirm($conformationMessage)) {
            return 0;
        }

        if (!$this->dryRun || $this->progress) {
            $this->deactivateTokens(clone $query, $total);
        }

        if ($this->dryRun && $verbose) {
            if ((clone $query)->count() === 0) {
                $this->printText("No tokens have to be deactivated.");
                return 0;
            }

            $this->printHeader("Deactivated tokens:", 2);
            $this->printDeactivatedProxies((clone $query), $this->chunkSize);
        }

        return 0;
    }

    /**
     * @param Builder|IdentityProxy $query
     * @param int $total
     * @return void
     */
    protected function deactivateTokens(Builder|IdentityProxy $query, int $total): void
    {
        $chunks = ceil($total / $this->chunkSize);

        (clone $query)->chunkById($this->chunkSize, function(Collection $proxies, $chunk) use ($chunks) {
            if (!$this->dryRun) {
                $proxies
                    ->filter(fn (IdentityProxy $proxy) => !$proxy->isDeactivated())
                    ->each(fn (IdentityProxy $proxy) => $proxy->deactivateBySession());
            }

            if ($this->progress) {
                $this->printProgress($chunk, $chunks);
            }
        });
    }

    /**
     * @param int $page
     * @param int $total
     * @return void
     */
    protected function printProgress(int $page, int $total): void
    {
        echo implode([
            $page !== 1 ? chr(27) . "[0G" : "",
            "Progress: $page/$total",
            $page == $total ? "\n\n" : "",
        ]);
    }

    /**
     * @param Builder|IdentityProxy $builder
     * @param int $chunkSize
     * @return void
     */
    protected function printDeactivatedProxies(Builder|IdentityProxy $builder, int $chunkSize): void
    {
        $builder->with('sessions_with_trashed');

        $builder->chunk($chunkSize, function($identityProxies) {
            $this->printList($identityProxies->map(function(IdentityProxy $proxy) {
                $line = sprintf("Token: #%s, date: %s", $proxy->id, $proxy->created_at);

                return [$line, $proxy->sessions_with_trashed->map(function(Session $session) {
                    return sprintf(
                        "Session: #%s, start: %s, end: %s, client: %s, trashed: %s",
                        $session->id,
                        $session->first_request->created_at,
                        $session->last_request->created_at,
                        $session->initial_client_type,
                        $session->deleted_at ? 'yes' : 'no',
                    );
                })->toArray()];
            })->toArray());
        });
    }

    /**
     * @return Builder
     */
    public function getIdentityProxiesQuery(): Builder
    {
        return IdentityProxy::where(function(Builder $builder) {
            // is older than 8 years
            $builder->where('created_at', '<', now()->subYears(8));

            // or is active, older than a month and has no sessions
            $builder->orWhere(fn(Builder $builder) => $this->queryActiveAndNoSessions($builder));

            // or is active has sessions and they all expired
            $builder->orWhere(function(Builder $builder) {
                $builder->where('state', IdentityProxy::STATE_ACTIVE);
                $builder->where(fn(Builder $builder) => $this->queryHasOnlyExpiredSessions($builder));
            });
        });
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public function queryHasOnlyExpiredSessions(Builder $builder): Builder
    {
        return $builder->where(function (Builder $builder) {
            $builder->whereHas('sessions', fn (Builder $b) => $this->queryExpiredSessions($b));
            $builder->whereDoesntHave('sessions', fn (Builder $b) => $this->queryNonExpiredSessions($b));
        });
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public function queryNonExpiredSessions(Builder $builder): Builder
    {
        $expiredBuilder = $this->queryExpiredSessions(Session::query());

        return $builder->whereNotIn('id', $expiredBuilder->select('id'));
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    public function queryExpiredSessions(Builder $builder): Builder
    {
        return $builder->where(function(Builder $builder) {
            // expired webshop sessions
            $builder->where(fn(Builder $builder) => $this->querySessionExpiredWebshop($builder));

            // expired dashboard sessions
            $builder->orWhere(fn(Builder $builder) => $this->querySessionExpiredDashboard($builder));

            // expired app sessions
            $builder->orWhere(fn(Builder $builder) => $this->querySessionExpiredApp($builder));
        });
    }

    /**
     * @param Builder|IdentityProxy $builder
     * @return Builder
     */
    private function queryActiveAndNoSessions(Builder|IdentityProxy $builder): Builder
    {
        $builder->where('state', IdentityProxy::STATE_ACTIVE);
        $builder->whereDoesntHave('sessions');

        $builder->where(function(Builder $builder) {
            $builder->where(function(Builder $builder) {
                $builder->whereNotNull('activated_at');
                $builder->where('activated_at', '<', now()->subMonth());
            });

            $builder->orWhere(function(Builder $builder) {
                $builder->whereNull('activated_at');
                $builder->where('created_at', '<', now()->subMonth());
            });
        });

        return $builder;
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    private function querySessionExpiredWebshop(Builder $builder): Builder
    {
        $value = Config::get('forus.sessions.webshop_expire_time.value');
        $unit = Config::get('forus.sessions.webshop_expire_time.unit');

        if (is_numeric($value)) {
            $builder->where(function (Builder $builder) use ($unit, $value) {
                $builder->where('last_activity_at', '<', now()->sub($unit, $value));
                $builder->whereRelation('first_request', function (Builder $builder) {
                    $builder->whereIn('client_type', [
                        Implementation::FRONTEND_WEBSHOP,
                    ]);
                });
            });
        }

        return $builder;
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    private function querySessionExpiredDashboard(Builder $builder): Builder
    {
        $value = Config::get('forus.sessions.dashboard_expire_time.value');
        $unit = Config::get('forus.sessions.dashboard_expire_time.unit');

        if (is_numeric($value)) {
            $builder->where(function (Builder $builder) use ($unit, $value) {
                $builder->where('last_activity_at', '<', now()->sub($unit, $value));
                $builder->whereRelation('first_request', function (Builder $builder) {
                    $builder->whereIn('client_type', [
                        Implementation::FRONTEND_WEBSITE,
                        Implementation::FRONTEND_PIN_CODE,
                        Implementation::FRONTEND_SPONSOR_DASHBOARD,
                        Implementation::FRONTEND_PROVIDER_DASHBOARD,
                        Implementation::FRONTEND_VALIDATOR_DASHBOARD,
                    ]);
                });
            });
        }

        return $builder;
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    private function querySessionExpiredApp(Builder $builder): Builder
    {
        $value = Config::get('forus.sessions.app_expire_time.value');
        $unit = Config::get('forus.sessions.app_expire_time.unit');

        if (is_numeric($value)) {
            $builder->where(function (Builder $builder) use ($unit, $value) {
                $builder->where('last_activity_at', '<', now()->sub($unit, $value));
                $builder->whereRelation('first_request', function (Builder $builder) {
                    $builder->whereIn('client_type', [
                        Implementation::ME_APP_DEPRECATED,
                        Implementation::ME_APP_ANDROID,
                        Implementation::ME_APP_IOS,
                    ]);
                });
            });
        }

        return $builder;
    }
}
