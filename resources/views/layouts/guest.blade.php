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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
@php
    $hideBackButton = $hideBackButton ?? false;
@endphp

<body class="font-sans antialiased">
    <div class="flex min-h-screen flex-col items-center bg-base-100 pt-6 sm:justify-center sm:pt-0 text-secondary">
        <div class="mx-auto">
            <a href="/" wire:navigate class="avatar">
                <x-application-logo class="w-0 sm:w-16 md:w-24" />
            </a>
        </div>
        <div class="sm:mt-6 w-full overflow-hidden bg-base-300 px-6 py-4 shadow-md sm:max-w-lg sm:rounded-lg flex flex-col">
            <div class="flex justify-between pb-2 mb-4 border-outline border-b">
                @if (!$hideBackButton)
                    <a href="javascript:history.back()" class="flex items-center hover:opacity-75">
                        <x-icon name="m-chevron-left" class="h-5 w-5" />
                        <span>Go Back</span>
                    </a>
                @endif
                <x-theme-toggle darkTheme="synthwave" class="pr-4"/>
            </div>

            {{ $slot }}
        </div>
    </div>
    <x-toast/>
    @livewireScripts
</body>
</html>
