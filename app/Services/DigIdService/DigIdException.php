<?php


namespace App\Services\DigIdService;

use Exception;

class DigIdException extends Exception
{
    protected $digIdErrorCode;

    /**
     * @return mixed
     */
    public function getDigIdCode() {
        return $this->digIdErrorCode;
    }

    /**
     * @param $errorCode
     * @return $this
     */
    public function setDigIdCode($errorCode): DigIdException
    {
        $this->digIdErrorCode = $errorCode;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return parent::__toString();
    }
}