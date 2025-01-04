<x-main-layout>
    <h2 class="font-extrabold text-2xl text-secondary text-center leading-tight">Profile</h2>
    <div class="py-4">
        <div
            class="container mx-auto px-4 sm:px-6 lg:px-8 flex flex-col items-center lg:justify-center lg:flex-row lg:items-stretch gap-4">
            <div
                class="p-4 bg-base-300 sm:rounded-lg max-w-xl flex flex-col items-center sm:items-start lg:items-start gap-2 flex-1 w-full">
                <header>
                    <h2 class="text-lg font-medium text-primary text-center">
                        {{ __('Account Information') }}
                    </h2>
                </header>
                <div class="flex flex-col items-center justify-around w-full gap-8">
                    <livewire:profile.update-profile-picture />
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>
            @if (!auth()->user()->isOauthAccount())
                <div class="shadow sm:rounded-lg flex flex-col mx-4 md:mx-0 md:flex-row gap-4 w-full md:w-fit ">
                    <div class="bg-base-300 p-4 rounded-xl max-w-xl">
                        <livewire:profile.update-password-form />
                    </div>
                </div>
            @endif
        </div>

        <div class="mx-4 flex justify-center md:mx-auto flex-1 max-w-7xl sm:px-6 lg:px-8 my-4">
            <livewire:profile.delete-user-form />
        </div>

    </div>
</x-main-layout>
