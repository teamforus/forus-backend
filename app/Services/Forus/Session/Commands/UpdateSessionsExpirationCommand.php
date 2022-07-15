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
    protected $signature = 'auth_sessions:update-expiration {--preview-only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invalidate expired sessions access tokens';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $identityProxies = $this->getIdentityProxies()->load([
            'sessions.first_request',
            'sessions.last_request',
        ])->loadCount('sessions');

        $previewOnly = $this->option('preview-only');

        if (!$previewOnly) {
            foreach ($identityProxies as $identityProxy) {
                $identityProxy->deactivateBySession();
            }
        }

        if ($identityProxies->isEmpty()) {
            $this->printText($this->green("No tokens have to be deactivated."));
            return 0;
        }

        $this->printHeader($this->green("Deactivated tokens:"), 2);
        $this->printList($identityProxies->map(function(IdentityProxy $proxy) {
            $line = sprintf("#%s, date: %s", $this->green($proxy->id), $this->green($proxy->created_at));

            return [$line, $proxy->sessions->map(function(Session $session) {
                return sprintf(
                    "#%s, start: %s, end: %s, client: %s",
                    $this->green($session->id),
                    $this->green($session->first_request->created_at),
                    $this->green($session->last_request->created_at),
                    $this->green($session->initial_client_type),
                );
            })->toArray()];
        })->toArray());

        return 0;
    }

    /**
     * @return Collection|IdentityProxy[]
     */
    protected function getIdentityProxies(): Collection|Array
    {
        $builder = IdentityProxy::query();

        // is older than 4 years
        $builder->where('created_at', '<', now()->subYears(4));

        $builder->orWhere(function(Builder $builder) {
            // or have no sessions and older than a month
            $builder->where(fn(Builder $builder) => $this->queryWhereActiveAndNoSessions($builder));

            // expired webshop sessions
            $builder->orWhere(fn(Builder $builder) => $this->querySessionExpiredWebshop($builder));

            // expired dashboard sessions
            $builder->orWhere(fn(Builder $builder) => $this->querySessionExpiredDashboard($builder));

            // expired app sessions
            $builder->orWhere(fn(Builder $builder) => $this->querySessionExpiredApp($builder));
        });

        return $builder->get();
    }

    /**
     * @param Builder $builder
     * @return Builder
     */
    private function queryWhereActiveAndNoSessions(Builder $builder): Builder
    {
        $builder->where('state', IdentityProxy::STATE_ACTIVE);
        $builder->whereDoesntHave('sessions_with_trashed');

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
            $builder->whereHas('sessions', function (Builder $builder) use ($unit, $value) {
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
            $builder->whereHas('sessions', function (Builder $builder) use ($unit, $value) {
                $builder->where('last_activity_at', '<', now()->sub($unit, $value));
                $builder->whereRelation('first_request', function (Builder $builder) {
                    $builder->whereIn('client_type', [
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
            $builder->whereHas('sessions', function (Builder $builder) use ($unit, $value) {
                $builder->where('last_activity_at', '<', now()->sub($unit, $value));
                $builder->whereRelation('first_request', function (Builder $builder) {
                    $builder->whereNotIn('client_type', [
                        Implementation::FRONTEND_WEBSHOP,
                        Implementation::FRONTEND_SPONSOR_DASHBOARD,
                        Implementation::FRONTEND_PROVIDER_DASHBOARD,
                        Implementation::FRONTEND_VALIDATOR_DASHBOARD,
                    ]);
                });
            });
        }

        return $builder;
    }
}
