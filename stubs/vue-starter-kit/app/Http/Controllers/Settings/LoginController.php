<?php

namespace App\Http\Controllers\Settings;

use ALajusticia\Logins\Models\Login;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\DisconnectLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Show the user's active logins.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('settings/Logins', [
            'logins' => $request->user()
                ->logins()
                ->latest('last_activity_at')
                ->get()
                ->map(fn (Login $login): array => [
                    'id' => $login->id,
                    'label' => $login->label ?: 'Unknown device',
                    'device_type' => $login->device_type,
                    'device' => $login->device,
                    'platform' => $login->platform,
                    'browser' => $login->browser,
                    'ip_address' => $login->ip_address,
                    'last_active' => $login->last_active,
                    'last_activity_at' => $login->last_activity_at?->toIso8601String(),
                    'created_at' => $login->created_at?->toIso8601String(),
                    'is_current' => $login->is_current,
                ])
                ->values(),
        ]);
    }

    /**
     * Disconnect one device/session.
     */
    public function destroy(DisconnectLoginRequest $request, int $login): RedirectResponse
    {
        $loginId = $request->user()
            ->logins()
            ->findOrFail($login)
            ->id;

        $request->user()->logout($loginId);

        return back();
    }

    /**
     * Disconnect all active devices/sessions.
     */
    public function destroyAll(DisconnectLoginRequest $request): RedirectResponse
    {
        $request->user()->logoutOthers();

        return back();
    }
}
