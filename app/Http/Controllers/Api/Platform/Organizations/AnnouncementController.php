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

        $search = new AnnouncementSearch($request, [], $organization);

        return AnnouncementResource::collection($search->query()->get());
    }
}