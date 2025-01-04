<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Component;

new class extends Component
{
    public string $trigger = '';
    public bool $showDeleteModal = false;

    /**
     * Delete the currently authenticated user.
     * If the user is an OAuth account, the user will be prompted to enter their email.
     * Otherwise, the user will be prompted to enter their password.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'trigger' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $user = auth()->user();
                    if (!$user->isOauthAccount()) {
                        // Validate as the current password
                        if (!\Hash::check($value, $user->password)) {
                            $fail('The current password is incorrect.');
                        }
                    } else {
                        // Validate that the value equals the user's email
                        if ($value !== $user->email) {
                            $fail('The value must match your email address.');
                        }
                    }
                },
            ],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
};
?>
<div>
    <x-button label="Delete Acount - Dangerous" class="btn-error" @click="$wire.showDeleteModal = true;" />
    <x-modal wire:model="showDeleteModal">
        <x-form wire:submit="deleteUser" class="p-6 flex flex-col gap-8">
            @csrf
            <h2 class="text-lg font-medium text-primary">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>

            <p class="text-sm text-secondary">
                Once your account is deleted, all of its resources and data will be permanently deleted. This includes your profile, tasks, labels, task comments and projects alongside the project notes and its attachments, project tasks, project labels,project invitations and project comments.
                @if (auth()->user()->isOauthAccount())
                    Please enter your email to confirm you would like to permanently delete your account.
                @else
                    Please enter your password to confirm you would like to permanently delete your account.
                @endif

            </p>

            @if (auth()->user()->isOauthAccount())
                <!-- Email -->
                <x-input label="Email" wire:model="trigger" icon="o-eye" type="email" />
            @else
                <!-- Password -->
                <x-input label="Password" wire:model="trigger" icon="o-eye" type="password" />
            @endif

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.showDeleteModal = false;" class="btn-outline" />
                <x-button label="Delete Account" type="submit" class="btn-error" />
            </x-slot:actions>
        </x-form>
    </x-modal>
</div>
