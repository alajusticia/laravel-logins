<?php

namespace App\Http\Controllers;

use ALajusticia\Logins\Models\Login;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoginsController extends Controller
{
    /**
     * Get the props expected by the published Vue component.
     *
     * @return array{logins: array<int, array<string, mixed>>, disconnectAllUrl: string}
     */
    public function props(Request $request): array
    {
        return [
            'logins' => $request->user()
                ->logins()
                ->latest('last_activity_at')
                ->get()
                ->map(fn (Login $login): array => [
                    'id' => $login->id,
                    'label' => filled($login->label) ? $login->label : null,
                    'device_type' => $login->device_type,
                    'device' => $login->device,
                    'platform' => $login->platform,
                    'browser' => $login->browser,
                    'ip_address' => $login->ip_address,
                    'last_active' => $login->last_active,
                    'last_activity_at' => $login->last_activity_at?->toIso8601String(),
                    'created_at' => $login->created_at?->toIso8601String(),
                    'is_current' => $login->is_current,
                    'disconnect_url' => route('logins.destroy', $login->getKey()),
                ])
                ->values()
                ->all(),
            'disconnectAllUrl' => route('logins.destroyOthers'),
        ];
    }

    /**
     * Disconnect a specific login belonging to the current user.
     */
    public function destroy(Request $request, int $login): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $loginId = $request->user()
            ->logins()
            ->findOrFail($login)
            ->id;

        $request->user()->logout($loginId);

        return back();
    }

    /**
     * Disconnect every login except the current session/token.
     */
    public function destroyOthers(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $request->user()->logoutOthers();

        return back();
    }
}
