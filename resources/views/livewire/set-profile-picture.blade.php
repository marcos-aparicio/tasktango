<?php

use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new
    #[Layout('layouts.guest', ['hideBackButton' => true])]
    class extends Component
    {
        use Toast;

        function mount()
        {
            if (!auth()->user()->profile_picture && !auth()->user()->has_asked_for_profile_picture) {
                return;
            }

            return redirect()->route('index');
        }

        function continue()
        {
            if (auth()->user()->profile_picture) {
                auth()->user()->has_asked_for_profile_picture = true;
                return redirect()->route('index');
            }
            $this->warning(
                title: 'Profile Picture Required',
                position: 'toast-bottom toast-end text-wrap',
                description: "Please select your profile picture before proceeding, if you don't want to click Skip for now. If you have changed your profile picture right now but can't move on wait a few seconds and click Next again",
                icon: 'o-exclamation-triangle'
            );
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
            <livewire:profile.update-profile-picture />

        </div>
    </div>
    <x-slot:actions>
        <x-button class="btn-outline" label="Skip for Now" wire:click="skipForNow" />
        <x-button class="btn-primary" label="Next" wire:click="continue" />

    </x-slot:actions>
</x-card>
