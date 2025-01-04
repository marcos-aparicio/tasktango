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
    @if($useFlatpickr ?? false)
    {{-- Flatpickr  --}}
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @endif
</head>

<body class="font-sans antialiased h-svh flex flex-col">

    {{-- The main content with `full-width` --}}
    <livewire:layout.navigation />

    <x-custom-main with-nav full-width class="flex-1 bg-base-100">
        <livewire:layout.sidebar />

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
