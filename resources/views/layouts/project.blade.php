<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- Scripts --}}
    @vite(['resources/css/app.css'])


    {{-- TODO: only load this when using notes --}}
    @if($useEasyMDE ?? false)
        {{-- EASYMDE --}}
        <link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
        <script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>
    @endif
    @if($useFlatpickr ?? false)
    {{-- Flatpickr  --}}
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @endif
</head>

<body class="font-sans antialiased h-svh flex flex-col">

    {{-- The main content with `full-width` --}}
    <livewire:layout.navigation-project />

    <x-custom-main with-nav full-width class="flex-1">
        <livewire:layout.project-sidebar />

        {{-- The `$slot` goes here --}}
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-custom-main>

    <livewire:project.inviting-modal />
    @php
        $layoutProject = \App\Models\Project::find(request()->project->id);
    @endphp
    @can('seeTaskActivity',$layoutProject)
        <livewire:project.task-activity-modal />
    @endcan
    @can('delete',$layoutProject)
        <livewire:project.modal-delete-project :project="$layoutProject"/>
    @endcan
    @can('complete',$layoutProject)
        <livewire:project.modal-complete-project :project="$layoutProject"/>
    @endcan
    @can('seeProjectActivity',$layoutProject)
        <livewire:project.project-activity-modal :project="$layoutProject"/>
    @endcan
    <livewire:task.modal-delete-task />
    {{--  TOAST area --}}
    <x-toast />
    @vite(['resources/js/app.js'])
</body>

</html>
