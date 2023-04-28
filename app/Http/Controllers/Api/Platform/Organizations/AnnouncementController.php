<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Announcements\IndexAnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Organization;
use App\Searches\AnnouncementSearch;
use App\Models\Announcement;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexAnnouncementRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function index(
        IndexAnnouncementRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [Announcement::class, $organization]);

        $search = new AnnouncementSearch([
            'client_type' => $request->client_type(),
            'organization_id' => $organization->id,
            'identity_address' => $request->auth_address(),
            'implementation_id' => $request->implementation()->id,
        ]);

        return AnnouncementResource::queryCollection($search->query());
    }
}