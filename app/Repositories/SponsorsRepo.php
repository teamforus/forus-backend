<?php
namespace App\Repositories;

use App\Models\Organization;
use App\Repositories\Interfaces\ISponsorsRepo;

class SponsorsRepo extends BaseRepo implements ISponsorsRepo
{
    public function __construct(
        Organization $model
    ) {
        $this->model = $model;
    }
}