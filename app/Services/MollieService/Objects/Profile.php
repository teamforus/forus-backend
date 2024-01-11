<?php

namespace App\Services\MollieService\Objects;

use Carbon\Carbon;

class Profile extends BaseObject
{
    /**
     * @var string
     */
    public string $id;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var string|null
     */
    public ?string $website = null;

    /**
     * @var string|null
     */
    public ?string $email = null;

    /**
     * @var string|null
     */
    public ?string $phone = null;

    /**
     * @var string
     */
    public string $status;

    /**
     * @var Carbon|null
     */
    public ?Carbon $created_at = null;
}
