<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Controllers\Controller;
use App\Http\Resources\IdentityEmailResource;
use App\Services\Forus\Identity\Models\IdentityEmail;
use App\Services\Forus\Identity\Repositories\Interfaces\IIdentityRepo;
use App\Services\Forus\Record\Repositories\Interfaces\IRecordRepo;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;

/**
 * Class IdentityEmailsController
 * @property IRecordRepo $rec
 * @property Notification $recordRepo
 * @property IIdentityRepo $identityRepo
 * @package App\Http\Controllers\Api\Identity
 */
class IdentityEmailsController extends Controller
{
    protected $identityRepo;
    protected $mailService;
    protected $recordRepo;

    /**
     * IdentityEmailsController constructor.
     *
     * @param IRecordRepo $recordRepo
     * @param IIdentityRepo $identityRepo
     */
    public function __construct(
        IRecordRepo $recordRepo,
        IIdentityRepo $identityRepo
    ) {
        $this->mailService = resolve('forus.services.notification');
        $this->identityRepo = $identityRepo;
        $this->recordRepo = $recordRepo;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('viewAny', IdentityEmail::class);

        return IdentityEmailResource::collection(
            IdentityEmail::where([
                'identity_address' => auth_address()
            ])->orderByDesc('primary')->orderBy('email')->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return IdentityEmailResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', IdentityEmail::class);

        $identityEmail = $this->identityRepo->addIdentityEmail(
            auth_address(),
            $request->input('email')
        );

        $identityEmail->sendVerificationEmail();

        return new IdentityEmailResource($identityEmail);
    }

    /**
     * Display the specified resource.
     *
     * @param IdentityEmail $identityEmail
     * @return IdentityEmailResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(IdentityEmail $identityEmail)
    {
        $this->authorize('view', $identityEmail);

        return new IdentityEmailResource($identityEmail);
    }

    /**
     * @param IdentityEmail $identityEmail
     * @return IdentityEmailResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function resend(IdentityEmail $identityEmail)
    {
        $this->authorize('resend', $identityEmail);

        $identityEmail->sendVerificationEmail();

        return new IdentityEmailResource($identityEmail);
    }

    /**
     * @param IdentityEmail $identityEmail
     * @return IdentityEmailResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function primary(IdentityEmail $identityEmail)
    {
        $this->authorize('makePrimary', $identityEmail);

        $identityEmail->makePrimary();
        return new IdentityEmailResource($identityEmail);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param IdentityEmail $identityEmail
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function destroy(IdentityEmail $identityEmail)
    {
        $this->authorize('delete', $identityEmail);

        $identityEmail->delete();

        return response()->json(null, 200);
    }

    /**
     * @param IdentityEmail $identityEmail
     * @return \Illuminate\Contracts\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function emailVerificationToken(IdentityEmail $identityEmail)
    {
        $this->authorize('verifyToken', $identityEmail);

        $identityEmail->update([
            'verified' => 1
        ]);

        return view('messages.email-verified');
    }
}
