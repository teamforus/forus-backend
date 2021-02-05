<?php /** @noinspection PhpIllegalPsrClassPathInspection */

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Services\Forus\Identity\Models\IdentityProxy;

class UpgradeIdentityProxiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('identity_proxies', function(Blueprint $table) {
            $table->string('type', 20)->after('id');
            $table->string('exchange_token', 200)->after('access_token');
        });

        IdentityProxy::withTrashed()->get()->each(function(IdentityProxy $identityProxy) {
            if (!empty($identityProxy['auth_code'])) {
                $identityProxy->update([
                    'type' => 'pin_code',
                    'exchange_token' => $identityProxy['auth_code'],
                ]);
            } else if (!empty($identityProxy['auth_token'])) {
                $identityProxy->update([
                    'type' => 'qr_code',
                    'exchange_token' => $identityProxy['auth_token'],
                ]);
            } else if (!empty($identityProxy['auth_email_token'])) {
                $identityProxy->update([
                    'type' => 'email_code',
                    'exchange_token' => $identityProxy['auth_email_token'],
                ]);
            } else {
                $identityProxy->update([
                    'type' => 'confirmation_code',
                    'exchange_token' => resolve('token_generator')->generate('128'),
                ]);
            }
        });

        Schema::table('identity_proxies', function(Blueprint $table) {
            $table->dropColumn('auth_code', 'auth_token', 'auth_email_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('identity_proxies', function(Blueprint $table) {
            $table->string('auth_token', 64)->nullable();
            $table->string('auth_code', 64)->nullable();
            $table->string('auth_email_token', 64)->nullable();
        });

        IdentityProxy::all()->each(function(IdentityProxy $identityProxy) {
            switch ($identityProxy->type) {
                case 'pin_code': $identityProxy->forceFill(['auth_code' => $identityProxy->exchange_token])->save(); break;
                case 'qr_code': $identityProxy->forceFill(['auth_token' => $identityProxy->exchange_token])->save(); break;
                case 'email_code': $identityProxy->forceFill(['auth_email_token' => $identityProxy->exchange_token])->save(); break;
            }
        });

        Schema::table('identity_proxies', function(Blueprint $table) {
            $table->dropColumn('type', 'exchange_token');
        });
    }
}
