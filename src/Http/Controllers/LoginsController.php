<?php

namespace ALajusticia\Logins\Http\Controllers;

use ALajusticia\Logins\Http\Resources\LoginResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class LoginsController extends Controller
{
    /**
     * List the current user's tracked logins.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return LoginResource::collection(
            $request->user()
                ->logins()
                ->latest('last_activity_at')
                ->get()
        );
    }

    /**
     * Disconnect a specific login belonging to the current user.
     */
    public function destroy(Request $request, int $loginId): Response
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $request->user()->logout($loginId);

        return response()->noContent();
    }

    /**
     * Disconnect every login except the current session/token.
     */
    public function destroyOthers(Request $request): Response
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $request->user()->logoutOthers();

        return response()->noContent();
    }

    /**
     * Disconnect every login, including the current session/token.
     */
    public function destroyAll(Request $request): Response
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $request->user()->logoutAll();

        return response()->noContent();
    }
}
