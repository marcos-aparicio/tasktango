@php
    $project = request()->project;
@endphp
{{-- The navbar with `sticky` and `full-width` --}}
<x-nav sticky full-width class="bg-base-300 lg:hidden z-50">
    <x-slot:brand>
        {{-- Drawer toggle for "main-drawer" --}}
        <label for="main-drawer" class="lg:hidden mr-3">
            <x-icon name="o-bars-3-center-left" class="cursor-pointer" />
        </label>

        <a href="{{ route('inbox') }}" wire:navigate class="btn btn-circle btn-ghost">
            <x-application-logo class="block h-6 md:h-9 w-auto text-primary" />
        </a>
    </x-slot:brand>

    {{-- Right side actions --}}
    <x-slot:actions>
        <x-button icon="o-magnifying-glass" link="{{ route('project.search', $project) }}" dusk='project-search-mobile-nav' class="btn-ghost"/>
    </x-slot:actions>
</x-nav>
