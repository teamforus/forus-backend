<?php


namespace App\Services\DigIdService;


class DigIdException extends \Exception
{
    protected $digIdErrorCode;

    public function getDigIdCode() {
        return $this->digIdErrorCode;
    }

    public function setDigIdCode($errorCode) {
        $this->digIdErrorCode = $errorCode;

        return $this;
    }

    public function __toString()
    {
        return parent::__toString();
    }
}