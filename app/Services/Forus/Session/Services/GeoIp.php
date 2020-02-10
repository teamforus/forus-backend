<?php


namespace App\Services\Forus\Session\Services;
use App\Services\Forus\Session\Services\Data\LocationData;
use GeoIp2\Database\Reader;
use GeoIp2\Record\City;
use GeoIp2\Record\Country;

/**
 * Class GeoIp
 * @package App\Services\Forus\Session\Services
 */
class GeoIp
{
    /**
     * @return \Illuminate\Config\Repository|mixed
     */
    public static function getDbPath() {
        return config('forus.sessions.geo_ip_db_path', false);
    }

    /**
     * @return \Illuminate\Filesystem\FilesystemAdapter|mixed
     */
    private static function getFileSystem() {
        return resolve('filesystem.disk');
    }

    private static function countryToString(Country $country) {
        return trans_fb('geo_ip/countries.' . $country->isoCode, $country->name);
    }

    private static function cityToString(City $city) {
        return trans_fb('geo_ip/cities.' . $city->geonameId, $city->name);
    }

    /**
     * @param string $ip
     * @return LocationData
     */
    public static function getLocation(string $ip) {
        if ($ip == '127.0.0.1') {
            return new LocationData($ip, "Local application");
        }

        if (self::isDatabaseAvailable()) {
            try {
                $reader = new Reader(self::getFileSystem()->path(self::getDbPath()));
                $record = $reader->city($ip);

                return new LocationData(
                    $ip,
                    self::countryToString($record->country),
                    self::cityToString($record->city)
                );
            } catch (\Exception $exception) {}
        }

        return new LocationData($ip, null, null);
    }

    /**
     * @return mixed
     */
    public static function isEnabled() {
        return config('forus.sessions.geo_ip_enabled', false);
    }

    /**
     * @return bool
     */
    public static function isDatabaseAvailable() {
        return self::getDbPath() && self::getFileSystem()->exists(self::getDbPath());
    }

    /**
     * @return bool
     */
    public static function isAvailable() {
        return self::isEnabled() && self::isDatabaseAvailable();
    }
}