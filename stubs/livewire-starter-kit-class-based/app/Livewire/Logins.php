<?php


use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Logins extends Component
{
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

        $this->redirect(route('login', absolute: false), navigate: true);
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

    public function render(): View
    {
        return view('livewire.settings.logins');
    }
}
