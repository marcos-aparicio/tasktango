<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public $probablyExistingAvatar;
    public $openedSubmenu = false;

    /**
     * Getting the profile picture when mounting(for the avatar to appear correctly)
     */
    public function mount()
    {
        $this->getProfilePictureUrl();
    }

    /**
     * Extracts the profile picture if exists and assigns it to the probablyExistingAvatar property
     */
    public function getProfilePictureUrl(): void
    {
        if (!auth()->user()->profile_picture) {
            $this->probablyExistingAvatar = null;
            return;
        }

        $this->probablyExistingAvatar = auth()->user()->getProfilePictureUrl();
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
};
?>

<nav class="bg-base-300 sticky top-0 z-30">
    <!-- Primary Navigation Menu -->
    <div class="navbar flex-1 gap-4">
        <!-- Leftside -->
        <div class="flex-1 flex">
            <!-- Logo -->
            <div class="flex shrink-0 items-center">
                <a href="{{ route('index') }}" wire:navigate>
                    <x-application-logo class="block h-9 w-auto text-primary" />
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                <a href="{{ route('index') }}"
                    class="link link-primary {{ request()->routeIs('index') ? '' : 'link-hover' }}"
                    wire:navigate>Inbox</a>
            </div>
        </div>

        <!-- Settings Dropdown -->
        <div class="hidden sm:flex sm:items-center">
            <x-dropdown>
                <x-slot:trigger class="flex items-center gap-1 cursor-pointer"
                    @profile-picture-updated.window="$wire.probablyExistingAvatar = $event.detail.profile_picture">
                    <div class="avatar">
                        <div class="w-10 rounded-full">
                            <img :src="$wire.probablyExistingAvatar" x-show="$wire.probablyExistingAvatar" />
                        </div>
                        <x-icon name="o-user-circle" class="!w-10 text-primary"
                            x-show="!$wire.probablyExistingAvatar" />
                    </div>
                    <x-icon name="o-chevron-down" class="!w-5 text-secondary" />
                </x-slot:trigger>
                <x-menu-item title="Profile" icon="o-user" link="{{ route('profile') }}" wire:navigate />
                <x-menu-item title="Log out" icon="o-arrow-left-start-on-rectangle" wire:click="logout" />
            </x-dropdown>
        </div>


        <!-- Theme Toggler -->
        <x-theme-toggle darkTheme="synthwave" />

        <!-- Responsive Menu Trigger -->
        <x-button icon="fas.bars" class="btn-circle" class="text-secondary sm:hidden self-center"
            @click="$wire.openedSubmenu = !$wire.openedSubmenu" />
    </div>

    <!-- Responsive Navigation Menu -->
    <x-menu class="sm:hidden" x-show="$wire.openedSubmenu" activate-by-route>
        <!--Routes -->
        <x-menu-item title="Inbox" class="text-primary font-bold border-l-4 border-primary rounded-none"
            link="{{ route('inbox') }}" />

        <!-- User info-->
        <x-menu-item class="pointer-events-none">
            <span class="text-primary font-bold">{{ auth()->user()->username }}</span>
            <br>
            <span class="text-secondary">{{ auth()->user()->email }}</span>
        </x-menu-item>

        <!-- Items-->
        <x-menu-item title="Profile" icon="o-user" link="{{ route('profile') }}" wire:navigate />
        <x-menu-item title="Log out" icon="o-arrow-left-start-on-rectangle" wire:click="logout" />
    </x-menu>

</nav>
