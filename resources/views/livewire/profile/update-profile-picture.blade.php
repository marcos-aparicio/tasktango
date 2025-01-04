<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new class extends Component
{
    use WithFileUploads;
    use Toast;

    public $avatar;
    public $probablyExistingAvatar;

    public function mount()
    {
        if (!auth()->user()->profile_picture) {
            $this->probablyExistingAvatar = null;
            return;
        }

        $this->probablyExistingAvatar = auth()->user()->getProfilePictureUrl();
    }

    public function updateProfilePicture()
    {
        $this->validate(
            [
                'avatar' => 'image|max:2048',  // 2MB Max
            ],
            [
                'avatar.image' => 'The avatar must be an image.',
                'avatar.max' => 'Your profile picture may not be greater than 1MB.',
            ],
        );

        $user = auth()->user();

        $diskToStore = config('filesystems.default') === 'local' ? 'public' : config('filesystems.default');
        $path = $this->avatar->store('profile_pictures', $diskToStore);

        if ($user->profile_picture) {
            Storage::delete($user->profile_picture);
        }

        if ($diskToStore === 'public') {
            $user->profile_picture = $path;
        } else {
            // making the temporaryUrl not that temporary
            $user->profile_picture = Storage::disk($diskToStore)->temporaryUrl($path, now()->addYears(100));
        }

        $user->save();

        $this->success(title: 'Profile picture updated successfully. Refresh your page to see the change', position: 'toast-bottom toast-end text-wrap');

        $this->dispatch('profile-picture-updated', profile_picture: $user->getProfilePictureUrl());
    }
};

?>

<section class="flex-1 w-full justify-center items-center">
    <x-form class="flex flex-col items-center" wire:submit="updateProfilePicture">
        @csrf
        <x-file wire:model="avatar" accept="image/png, image/jpeg"
            class="relative text-wrap mx-auto flex flex-col items-center">
            <div class="avatar">
                <div class="w-32 relative rounded-full overflow-hidden" >
                    <x-icon name="c-user-circle" class="text-secondary" x-show="!$wire.avatar && !$wire.probablyExistingAvatar" x-cloak class="!w-32 text-secondary"/>
                    <img src="{{$probablyExistingAvatar ?? ''}}">
                </div>
            </div>
            <div class="absolute bottom-0 right-0 bg-primary text-white rounded-full p-2 w-8 h-8 flex items-center">
                <x-icon name="o-camera" class="!w-8 rounded-full" />
            </div>
        </x-file>
        <x-slot:actions>
            <x-button label="Save" type="submit" class="btn btn-primary btn-sm !uppercase mx-auto" />
        </x-slot:actions>
    </x-form>
</section>
