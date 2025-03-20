<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new
    #[Layout('layouts.guest', ['hideBackButton' => true])]
    class extends Component
    {
        use WithFileUploads;
        use Toast;

        public $avatar;

        function mount()
        {
            if (!auth()->user()->profile_picture && !auth()->user()->has_asked_for_profile_picture) {
                return;
            }

            return redirect()->route('index');
        }

        function setPicture()
        {
            if (auth()->user()->profile_picture) {
                auth()->user()->has_asked_for_profile_picture = true;
                return redirect()->route('index');
            }
            try {
                $validated = $this->validate(
                    ['avatar' => 'image|max:2048'],
                    [
                        'avatar.image' => 'The avatar must be an image.',
                        'avatar.max' => 'Your profile picture may not be greater than 1MB.',
                    ]
                );

                // Validation passed, proceed with logic
            } catch (ValidationException $e) {
                $errors = $e->validator->errors();  // Get all validation errors

                // Example: Get all error messages as an array
                $errorMessages = $errors->all();

                // Example: Get errors for a specific field
                $avatarErrors = $errors->get('avatar');

                // Handle errors (store them in session, return JSON, etc.)
                $this->warning(
                    title: $avatarErrors[0],
                    position: 'toast-bottom toast-end text-wrap',
                    icon: 'o-exclamation-triangle'
                );
                return;
            }

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
            return redirect()->route('index');
        }

        function skipForNow()
        {
            auth()->user()->has_asked_for_profile_picture = true;
            return redirect()->route('index');
        }
    };

?>

<x-card class="bg-base-200" title="Set your profile picture" subtitle="Complete your profile with a personal touch">
    <div class="flex flex-col">
        <div class="flex items-center justify-center w-full flex-1">
          <x-form class="flex flex-col items-center" wire:submit="updateProfilePicture">
              @csrf
              <x-file wire:model="avatar" accept="image/png, image/jpeg" name="profile-picture"
                  class="relative text-wrap mx-auto flex flex-col items-center">
                  <div class="avatar">
                      <div class="w-32 relative rounded-full overflow-hidden" >
                          <x-icon name="c-user-circle" class="text-secondary" class="!w-32 text-secondary" x-show="!$wire.avatar"/>
                          <img src="">
                      </div>
                  </div>
                  <div class="absolute bottom-0 right-0 bg-primary text-white rounded-full p-2 w-8 h-8 flex items-center">
                      <x-icon name="o-camera" class="!w-8 rounded-full" />
                  </div>
              </x-file>
              <x-slot:actions>
                <x-button class="btn-outline" label="Skip for Now" wire:click="skipForNow" />
                <x-button class="btn-primary" label="Set Picture" wire:click="setPicture" />
              </x-slot:actions>
          </x-form>
        </div>
    </div>
</x-card>
