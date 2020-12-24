<?php


namespace App\Services\Forus\Session\Services\Data;

use WhichBrowser\Parser;

/**
 * Class LocationData
 * @package App\Services\Forus\Session\Services\Data
 */
class AgentData extends Parser
{
    /**
     * @param string $user_agent
     * @return AgentData
     */
    public static function parse(string $user_agent): AgentData
    {
        return new self($user_agent);
    }

    /**
     * Return the input string prefixed with 'a' or 'an' depending on the first letter of the string
     *
     * @param  string $s The string that will be prefixed
     * @return string
     */
    private function a($s): string
    {
        // return (preg_match("/^[aeiou]/i", $s) ? 'an ' : 'a ') . $s;
        return 'een ';
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        try {
            $prefix = $this->camouflage ? 'een onbekende imititatie browser van ' : '';
            $browser = $this->browser->toString();
            $os = $this->os->toString();
            $engine = $this->engine->toString();
            $device = $this->device->toString();

            if (empty($device) && empty($os) && $this->device->type == 'television') {
                $device = 'televisie';
            }

            if (empty($device) && $this->device->type == 'emulator') {
                $device = 'emulator';
            }

            if (!empty($browser) && !empty($os) && !empty($device)) {
                return $prefix . $browser . ' op ' . $this->a($device) . ' heeft besturingssysteem: ' . $os;
            }

            if (!empty($browser) && empty($os) && !empty($device)) {
                return $prefix . $browser . ' op ' . $this->a($device);
            }

            if (!empty($browser) && !empty($os) && empty($device)) {
                return $prefix . $browser . ' op ' . $os;
            }

            if (empty($browser) && !empty($os) && !empty($device)) {
                return $prefix . $this->a($device) . ' heeft besturingssysteem: ' . $os;
            }

            if (!empty($browser) && empty($os) && empty($device)) {
                return $prefix . $browser;
            }

            if (empty($browser) && empty($os) && !empty($device)) {
                return $prefix . $this->a($device);
            }

            if ($this->device->type == 'desktop' && !empty($os) && !empty($engine) && empty($device)) {
                return 'een onbekende browser gebasseerd op ' . $engine . ' heeft besturingssysteem: ' . $os;
            }

            if ($this->browser->stock && !empty($os) && empty($device)) {
                return $os;
            }

            if ($this->browser->stock && !empty($engine) && empty($device)) {
                return 'een onbekende browser gebasseerd op ' . $engine;
            }

            if ($this->device->type == 'bot') {
                return 'onbekende bot';
            }

            return 'Client onbekend';
        } catch (\Exception $exception) {
            return parent::toString();
        }
    }
}