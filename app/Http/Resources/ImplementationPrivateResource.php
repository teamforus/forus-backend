<?php

namespace App\Http\Resources;

use App\Helpers\Markdown;
use App\Http\Requests\BaseFormRequest;
use App\Models\Announcement;
use App\Models\Implementation;
use App\Models\ImplementationPage;
use League\CommonMark\Exception\CommonMarkException;

class ImplementationPrivateResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return ?array
     * @throws CommonMarkException
     * @property Implementation $resource
     */
    public function toArray($request): ?array
    {
        $request = BaseFormRequest::createFrom($request);

        /** @var Implementation $implementation **/
        if (is_null($implementation = $this->resource)) {
            return null;
        }
      
        $data = array_merge($implementation->only([
            'id', 'key', 'name', 'url_webshop', 'title', 'organization_id',
            'description', 'description_alignment', 'description_html', 'informal_communication',
            'overlay_enabled', 'overlay_type', 'overlay_opacity', 'header_text_color',
            'show_home_map', 'show_home_products', 'show_providers_map', 'show_provider_map',
            'show_office_map', 'show_voucher_map', 'show_product_map',
            'allow_per_fund_notification_templates',
            'pre_check_enabled', 'pre_check_title', 'pre_check_description',
            'pre_check_homepage_title', 'pre_check_homepage_description', 'pre_check_homepage_label',
        ]), [
            'communication_type' => $implementation->informal_communication ? 'informal' : 'formal',
            'overlay_opacity' => min(max(intval($implementation->overlay_opacity / 10) * 10, 0), 100),
            'banner' => new MediaResource($implementation->banner),
            'pre_check_banner' => new MediaResource($implementation->pre_check_banner),
            'announcement' => $this->getAnnouncement($implementation),
        ]);

        $data = array_merge($data, [
            'pages' => ImplementationPageResource::collection($implementation->pages),
            'page_types' => array_map(fn(array $pageType) => array_merge($pageType, [
                'webshop_url' => $implementation->urlWebshop(ImplementationPage::webshopUriByPageType($pageType['key'])),
            ]), ImplementationPage::PAGE_TYPES),
        ]);

        return array_merge(
            $data,
            $this->managerDetails($request, $implementation),
            $this->managerCMSDetails($request, $implementation)
        );
    }

    /**
     * @param BaseFormRequest $request
     * @param Implementation $implementation
     * @return array
     */
    protected function managerDetails(
        BaseFormRequest $request,
        Implementation $implementation
    ): array {
        if ($implementation->organization->identityCan($request->identity(), [
            'implementation_manager',
        ])) {
            return $implementation->only([
                'digid_app_id', 'digid_shared_secret', 'digid_a_select_server', 'digid_enabled',
                'email_from_address', 'email_from_name',
            ]);
        }

        return [];
    }

    /**
     * @param BaseFormRequest $request
     * @param Implementation $implementation
     * @return array
     * @throws CommonMarkException
     */
    protected function managerCMSDetails(
        BaseFormRequest $request,
        Implementation $implementation
    ): array {
        $generalImplementation = $implementation::general();

        if ($implementation->organization->identityCan($request->identity(), 'manage_implementation_cms')) {
            return [
                'email_logo' => new MediaCompactResource($implementation->email_logo),
                'email_logo_default' => new MediaCompactResource($generalImplementation->email_logo),
                'email_color' => trim(strtoupper($implementation->email_color)),
                'email_color_default' => trim(strtoupper($generalImplementation->email_color)),
                'email_signature' => trim($implementation->email_signature ?: ''),
                'email_signature_html' => Markdown::convert($implementation->email_signature ?: ''),
                'email_signature_default' => trim($generalImplementation->email_signature ?: ''),
            ];
        }

        return [];
    }

    /**
     * @param Implementation $implementation
     * @return array|null
     */
    private function getAnnouncement(Implementation $implementation): ?array
    {
        /** @var Announcement $announcement */
        if (!$announcement = $implementation->announcements_webshop()->first()) {
            return null;
        }

        return array_merge($announcement->only([
            'id', 'type', 'title', 'description', 'description_html', 'scope', 'active',
        ]), [
            'expire_at' => $announcement->expire_at?->format('Y-m-d'),
        ]);
    }
}
