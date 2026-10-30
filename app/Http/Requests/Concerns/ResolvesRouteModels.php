<?php

namespace App\Http\Requests\Concerns;

use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Models\Organization;
use LogicException;

trait ResolvesRouteModels
{
    /**
     * @return Organization
     */
    protected function getRouteOrganization(): Organization
    {
        $organization = $this->route('organization');

        if (!$organization instanceof Organization) {
            throw new LogicException('Invalid organization route context.');
        }

        return $organization;
    }

    /**
     * @return Implementation
     */
    protected function getRouteImplementation(): Implementation
    {
        $implementation = $this->route('implementation');

        if (!$implementation instanceof Implementation) {
            throw new LogicException('Invalid implementation route context.');
        }

        return $implementation;
    }

    /**
     * @return ImplementationPage
     */
    protected function getRouteImplementationPage(): ImplementationPage
    {
        $implementationPage = $this->route('implementationPage');

        if (!$implementationPage instanceof ImplementationPage) {
            throw new LogicException('Invalid implementation page route context.');
        }

        return $implementationPage;
    }
}
