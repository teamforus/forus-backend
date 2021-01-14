<?php

namespace App\Http\Resources;

use App\Models\Implementation;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Organization;
use App\Scopes\Builders\OrganizationQuery;

class ImplementationPrivateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @property Implementation $resource
     * @return array
     */
    public function toArray($request)
    {
        /** @var Implementation $implementation **/
        if (is_null($implementation = $this->resource)) {
            return null;
        }

        /** @var Organization $organization */
        $organization = OrganizationQuery::whereImplementationIdFilter(
            Organization::query(),
            $implementation->id
        )->first() or abort(403);

        $data = $implementation->only([
            'id', 'key', 'name', 'url_webshop', 'title',
            'description', 'has_more_info_url', 'more_info_url',
            'description_steps', 'privacy_page'
        ]);

        if ($organization->identityCan(auth()->id(), 'implementation_manager')) {
            $data = array_merge($data, $implementation->only([
                'digid_app_id', 'digid_shared_secret',
                'digid_a_select_server', 'digid_enabled',
                'email_from_address', 'email_from_name'
            ]));
        }

        return $data;
    }
}
