<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Bookmarks\RemoveBookmarkRequest;
use App\Http\Requests\Api\Platform\Bookmarks\SetBookmarkRequest;
use App\Http\Resources\BookmarkResource;
use App\Models\Bookmark;
use Illuminate\Http\JsonResponse;

class BookmarkController extends Controller
{
    /**
     * @param SetBookmarkRequest $request
     * @return BookmarkResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function setBookmark(SetBookmarkRequest $request): BookmarkResource
    {
        $this->authorize('setBookmark', Bookmark::class);

        $bookmark = Bookmark::query()->create([
            'identity_address' => $request->auth_address(),
            'bookmarkable_id'  => $request->input('bookmarkable_id'),
            'bookmarkable_type' => $request->input('bookmarkable_type'),
        ]);

        return new BookmarkResource($bookmark);
    }

    /**
     * @param RemoveBookmarkRequest $request
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function removeBookmark(RemoveBookmarkRequest $request): JsonResponse
    {
        $this->authorize('removeBookmark', Bookmark::class);

        Bookmark::query()->where([
            'identity_address' => $request->auth_address(),
            'bookmarkable_id'  => $request->input('bookmarkable_id'),
            'bookmarkable_type' => $request->input('bookmarkable_type'),
        ])->delete();

        return response()->json([]);
    }
}
