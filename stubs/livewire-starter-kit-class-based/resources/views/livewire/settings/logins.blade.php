<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Active sessions') }}</flux:heading>

    <x-settings.layout
        :heading="__('Active sessions')"
        :subheading="__('Manage devices and web browsers signed in to your account')"
    >
        <div class="space-y-6">
            <flux:text variant="subtle">
                {{ __('Review your active devices and disconnect any sessions you no longer recognize or trust.') }}
            </flux:text>

            @if ($this->logins->isEmpty())
                <flux:callout
                    icon="information-circle"
                    heading="{{ __('No active logins were found.') }}"
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
                                            <flux:badge color="green">
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
                                variant="danger"
                                size="sm"
                                wire:click="confirmDisconnectLogin({{ $login->id }})"
                            >
                                {{ __('Disconnect') }}
                            </flux:button>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center gap-3">
                    <flux:button variant="danger" wire:click="confirmDisconnectAllOtherDevices">
                        {{ __('Disconnect all other sessions') }}
                    </flux:button>

                    <x-action-message on="logins-updated">
                        {{ __('Updated.') }}
                    </x-action-message>
                </div>
            @endif
        </div>
    </x-settings.layout>

    <flux:modal wire:model="showDisconnectLoginModal" class="max-w-lg">
        <form method="POST" wire:submit="disconnectSelectedDevice" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Disconnect this device?') }}</flux:heading>

                <flux:subheading>
                    {{ __('Enter your password to confirm disconnecting this device session.') }}
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
                <flux:heading size="lg">{{ __('Disconnect all other sessions?') }}</flux:heading>

                <flux:subheading>
                    {{ __('Enter your password to confirm. This will sign you out from every device, except the current one.') }}
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
                    {{ __('Disconnect all other sessions') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
