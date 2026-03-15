<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    public string $password = '';

    public bool $showDisconnectAllModal = false;

    public bool $showDisconnectLoginModal = false;

    #[Locked]
    public ?int $selectedLoginId = null;

    /**
     * Get the authenticated user's tracked logins.
     *
     * @return Collection<int, \ALajusticia\Logins\Models\Login>
     */
    #[Computed]
    public function logins(): Collection
    {
        return Auth::user()->logins()->latest('last_activity_at')->get();
    }

    /**
     * Open the confirmation modal to disconnect all other devices.
     */
    public function confirmDisconnectAllOtherDevices(): void
    {
        $this->resetErrorBag();
        $this->password = '';
        $this->showDisconnectAllModal = true;
    }

    /**
     * Open the confirmation modal for a specific login.
     */
    public function confirmDisconnectLogin(int $loginId): void
    {
        $login = Auth::user()->logins()->find($loginId);

        abort_unless($login, 404);

        $this->resetErrorBag();
        $this->password = '';
        $this->selectedLoginId = $login->id;
        $this->showDisconnectLoginModal = true;
    }

    /**
     * Close the "disconnect all other devices" modal.
     */
    public function cancelDisconnectAllOtherDevices(): void
    {
        $this->resetErrorBag();
        $this->reset('password', 'showDisconnectAllModal');
    }

    /**
     * Close the "disconnect selected device" modal.
     */
    public function cancelDisconnectLogin(): void
    {
        $this->resetErrorBag();
        $this->reset('password', 'selectedLoginId', 'showDisconnectLoginModal');
    }

    /**
     * Disconnect all tracked devices, excepting the current one.
     */
    public function disconnectAllOtherDevices(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        Auth::user()->logoutOthers();

        $this->cancelDisconnectAllOtherDevices();
        $this->showDisconnectLoginModal = false;
        $this->selectedLoginId = null;

        $this->dispatch('logins-updated');
    }

    /**
     * Disconnect the selected device.
     */
    public function disconnectSelectedDevice(): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $selectedLogin = Auth::user()->logins()->find($this->selectedLoginId);

        abort_unless($selectedLogin, 404);

        $disconnectingCurrentDevice = $selectedLogin->is_current;

        Auth::user()->logout($selectedLogin->id);

        $this->cancelDisconnectLogin();

        if ($disconnectingCurrentDevice) {
            $this->redirect(route('login', absolute: false), navigate: true);

            return;
        }

        $this->dispatch('logins-updated');
    }
}; ?>

<section {{ $attributes->merge(['class' => 'w-full space-y-6']) }}>
    <div class="space-y-1">
        <flux:heading>{{ __('Active sessions') }}</flux:heading>
        <flux:subheading>{{ __('Manage the devices signed in to your account') }}</flux:subheading>
    </div>

    @if ($this->logins->isEmpty())
        <flux:callout
            icon="information-circle"
            heading="{{ __('No active sessions were found.') }}"
        />
    @else
        <div class="space-y-3">
            @foreach ($this->logins as $login)
                <div
                    wire:key="login-{{ $login->id }}"
                    class="flex flex-col gap-4 rounded-xl border border-zinc-200 p-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 text-zinc-500 dark:text-zinc-400">
                            @if ($login->device_type === 'desktop')
                                <flux:icon.computer-desktop variant="outline" class="size-5" />
                            @elseif ($login->device_type === 'tablet')
                                <flux:icon.device-tablet variant="outline" class="size-5" />
                            @else
                                <flux:icon.device-phone-mobile variant="outline" class="size-5" />
                            @endif
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <flux:text class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ filled($login->label) ? $login->label : __('Unknown device') }}
                                </flux:text>

                                @if ($login->is_current)
                                    <flux:badge color="green" size="sm">
                                        {{ __('This device') }}
                                    </flux:badge>
                                @endif
                            </div>

                            <flux:text size="sm" variant="subtle">
                                {{ $login->ip_address ?: __('Unknown IP address') }}
                            </flux:text>

                            @if (! $login->is_current)
                                <flux:text size="sm" variant="subtle">
                                    {{ __('Last active :time', ['time' => $login->last_active]) }}
                                </flux:text>
                            @endif
                        </div>
                    </div>

                    <flux:button
                        variant="filled"
                        size="xs"
                        wire:click="confirmDisconnectLogin({{ $login->id }})"
                    >
                        {{ __('Disconnect') }}
                    </flux:button>
                </div>
            @endforeach
        </div>

        <div class="flex items-center gap-3">
            <flux:button variant="danger" wire:click="confirmDisconnectAllOtherDevices">
                {{ __('Disconnect all other devices') }}
            </flux:button>

            <x-action-message on="logins-updated">
                {{ __('Updated.') }}
            </x-action-message>
        </div>
    @endif

    <flux:modal wire:model="showDisconnectLoginModal" class="max-w-lg">
        <form method="POST" wire:submit="disconnectSelectedDevice" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Disconnect this device?') }}</flux:heading>

                <flux:subheading>
                    {{ __('Enter your password to confirm disconnecting this device.') }}
                </flux:subheading>
            </div>

            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                autocomplete="current-password"
            />

            <div class="flex justify-end gap-2">
                <flux:button variant="filled" type="button" wire:click="cancelDisconnectLogin">
                    {{ __('Cancel') }}
                </flux:button>

                <flux:button variant="danger" type="submit">
                    {{ __('Disconnect device') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showDisconnectAllModal" class="max-w-lg">
        <form method="POST" wire:submit="disconnectAllOtherDevices" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Disconnect all other devices?') }}</flux:heading>

                <flux:subheading>
                    {{ __('Enter your password to confirm you want to disconnect all other devices from your account. Your current session will remain active.') }}
                </flux:subheading>
            </div>

            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                autocomplete="current-password"
            />

            <div class="flex justify-end gap-2">
                <flux:button variant="filled" type="button" wire:click="cancelDisconnectAllOtherDevices">
                    {{ __('Cancel') }}
                </flux:button>

                <flux:button variant="danger" type="submit">
                    {{ __('Disconnect all other devices') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
