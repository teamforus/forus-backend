<?php


namespace App\Services\Forus\Session\Services\Data;

class LocationData
{
    public string $ip;
    public ?string $country;
    public ?string $city;
    public ?string $string;
    public bool $detected;

    /**
     * LocationData constructor.
     * @param string $ip
     * @param string|null $country
     * @param string|null $city
     */
    public function __construct(
        string $ip,
        string $country = null,
        string $city = null
    ) {
        $this->ip = $ip;
        $this->country = $country;
        $this->city = $city;

        $this->string = $this->toString();
        $this->detected = $this->isDetected();
    }

    /**
     * @return string|null
     */
    public function toString() {
        if ($this->country && $this->city) {
            return sprintf("%s, %s", $this->country, $this->city);
        } else if ($this->country && !$this->city) {
            return $this->country;
        } else if ($this->city && !$this->country) {
            return $this->city;
        } else {
            return trans("agent.unknown");
        }
    }

    /**
     * @return bool
     */
    public function isDetected() {
        return !empty($this->country) || !empty($this->city);
    }
}
