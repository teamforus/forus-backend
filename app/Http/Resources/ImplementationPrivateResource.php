<?php

namespace App\Http\Resources;

use App\Models\Implementation;
use App\Models\ImplementationPage;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ImplementationPrivateResource
 * @package App\Http\Resources
 */
class ImplementationPrivateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @property Implementation $resource
     * @return array
     */
    public function toArray($request): ?array
    {
        /** @var Implementation $implementation **/
        if (is_null($implementation = $this->resource)) {
            return null;
        }

        $data = $implementation->only([
            'id', 'key', 'name', 'url_webshop', 'title', 'description',
        ]);

        $data = array_merge($data, [
            'pages' => array_reduce(ImplementationPage::TYPES, function(
                array $pages, string $type
            ) use ($implementation) {
                $page = $implementation->pages->where('page_type', $type)->first();

                return array_merge($pages, [
                    $type => $page ? $this->pageDetails($page) : null,
                ]);
            }, []),
            'page_types' => ImplementationPage::TYPES,
            'page_types_internal' => ImplementationPage::TYPES_INTERNAL
        ]);

        if ($implementation->organization->identityCan(auth()->id(), 'implementation_manager')) {
            $data = array_merge($data, $implementation->only([
                'digid_app_id', 'digid_shared_secret', 'digid_a_select_server', 'digid_enabled',
                'email_from_address', 'email_from_name',
            ]));
        }

        return $data;
    }

    /**
     * @param ImplementationPage|null $page
     * @return array|null
     */
    protected function pageDetails(?ImplementationPage $page): ?array
    {
        return $page ? $page->only([
            'page_type', 'content', 'external', 'external_url',
        ]) : null;
    }
}
