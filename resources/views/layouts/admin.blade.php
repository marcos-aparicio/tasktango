<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css'])
</head>

<body class="font-sans antialiased h-svh flex flex-col">

    {{-- The main content with `full-width` --}}
    <livewire:layout.navigation />

    <x-custom-main with-nav full-width class="flex-1">
        @php
            $user = auth()->user();
        @endphp
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-200">
            <x-menu activate-by-route active-bg-color="bg-purple-500/20">
                <x-list-item :item="$user" value="full_name" sub-value="email" no-separator no-hover
                    class="-mx-2 !-my-2 rounded">
                    <x-slot:avatar>
                        <x-avatar-or-icon :user="$user" avatar-class="!w-10" icon-class="hidden"/>
                    </x-slot:avatar>
                    <x-slot:actions>
                        <x-theme-toggle dusk="theme-controller" darkTheme="synthwave" class="!w-6" />
                    </x-slot:actions>
                </x-list-item>

                <x-menu-item icon="o-arrow-left-start-on-rectangle" title="Log out" tooltip-right="Log out" no-wire-navigate
                    link="{{route('logout')}}" dusk="logout"/>
                <x-menu-item title="Profile" icon="o-cog-6-tooth" link="{{ route('profile') }}" route="profile" />
                <x-menu-item title="Users" icon="o-users" link="{{ route('admin.users') }}" route="admin.users" />
                <x-menu-item title="Projects" icon="o-folder" link="{{ route('admin.projects') }}" route="admin.projects" />
                <x-menu-separator />
            </x-menu>
        </x-slot:sidebar>

        {{-- The `$slot` goes here --}}
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-custom-main>

    <livewire:project.create-modal />
    <livewire:project.invitations-modal />
    <livewire:task.modal-delete-task />
    {{--  TOAST area --}}
    <x-toast />
    @vite(['resources/js/app.js'])
</body>

</html>
