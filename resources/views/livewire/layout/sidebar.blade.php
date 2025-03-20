@php
    $user = auth()->user();
@endphp
{{-- This is a sidebar that works also as a drawer on small screens --}}
{{-- Notice the `main-drawer` reference here --}}
{{-- It is only intented to work inside an x-main  tag of MaryUI or my custom modification of that tag --}}
<div>
    <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-200">
        <x-menu activate-by-route active-bg-color="bg-purple-500/20">

            @impersonating($guard = null)
                <x-menu-item icon="o-arrow-long-left" title="Return to Admin Panel" link="{{route('impersonate.leave')}}" />
            @else
                <x-menu-item icon="o-arrow-long-left" title="Go back" x-on:click="window.history.back()" />
            @endImpersonating
            <x-list-item :item="$user" value="full_name" sub-value="email" no-separator no-hover
                class="-mx-2 !-my-2 rounded">
                <x-slot:avatar>
                    <x-avatar-or-icon dusk="profile-avatar" :user="$user" avatar-class="!w-10"/>
                </x-slot:avatar>
                <x-slot:actions>
                    <x-theme-toggle dusk="theme-controller" darkTheme="synthwave" class="!w-6" />
                </x-slot:actions>
            </x-list-item>

            @impersonating($guard = null)
            @else
                <x-menu-item icon="o-arrow-left-start-on-rectangle" title="Log out" tooltip-right="Log out" no-wire-navigate
                link="{{route('logout')}}" dusk="logout"/>
            @endImpersonating
            <x-menu-item title="Profile" icon="o-cog-6-tooth" link="{{ route('profile') }}" route="profile" />
            <x-menu-item title="Search" icon="o-magnifying-glass" link="{{ route('search') }}" route="search" dusk='search'/>

            <x-menu-separator />
            <x-menu-item title="Inbox" icon="o-inbox" link="{{ route('inbox') }}" route="inbox" dusk='inbox'/>
            <x-menu-item title="Today" icon="o-check-badge" link="{{ route('today') }}" route="today" />
            <x-menu-item title="Next 7 days" icon="o-calendar-days" link="{{ route('next-7-days') }}"
                route="next-7-days" dusk="next-7-days"/>
            <x-menu-item title="Calendar" icon="o-calendar-date-range" link="{{ route('calendar') }}" route="calendar"
                no-wire-navigate />
            <x-menu-item title="Labels" icon="o-tag" link="{{ route('labels') }}" route="labels" />
            @php
                $user = auth()->user();
                $invitations = $user->receivedProjectInvitationsValid();
                $count = $invitations->count();
                if ($count > 10) {
                    $count = '10+';
                }
            @endphp
            <x-custom-menu-sub title="Projects" icon="o-folder-open" badge="{{$count}}" badge-classes="!badge-warning">
                <x-menu-item title="Create New" icon="o-folder-plus" x-on:click="$dispatch('new-project-modal')" />
                <x-menu-item title="View all" icon="o-clipboard-document-list" link="{{route('projects')}}" route="projects"/>
                <x-menu-item title="Project Invitations" icon="o-envelope" badge="{{$count}}"
                badge-classes="float-right !badge-warning" x-on:click="$dispatch('project-invitation-modal-open')" />
            </x-custom-menu-sub>
        </x-menu>
    </x-slot:sidebar>
</div>
