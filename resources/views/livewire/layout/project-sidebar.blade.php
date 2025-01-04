@php
    $user = auth()->user();
    $project = request()->project;
    $current_user_role = $project->user()->pivot->role;
    $role_name = $project->getRoleName($user);
@endphp
{{-- This is a sidebar that works also as a drawer on small screens --}}
{{-- Notice the `main-drawer` reference here --}}
{{-- It is only intented to work inside an x-main  tag of MaryUI or my custom modification of that tag --}}
{{-- TODO: complete the sidebar with its correspondent links and functionality --}}
<div>
    <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-200">
        <x-menu activate-by-route active-bg-color="bg-purple-500/20">
            @impersonating($guard = null)
                <x-menu-item icon="o-arrow-long-left" title="Return to Admin Panel" link="{{route('impersonate.leave')}}" />
            @endImpersonating
            <x-list-item :item="$user" value="full_name" sub-value="email" no-separator no-hover
                class="-mx-2 !-my-2 rounded">
                <x-slot:avatar>
                    <x-avatar-or-icon :user="$user" avatar-class="!w-10"/>
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

            <x-menu-separator />
            <x-menu-item title="Return to main view" icon="o-arrow-uturn-left" link="{{route('inbox')}}"/>
            <x-menu-item title="Search in Project" icon="o-magnifying-glass" link="{{ route('project.search',[$project]) }}" route="project.search" dusk='search'/>
            <x-menu-separator />
            @php
                $projectName = $project->name;
                if($project->status == App\Enums\TaskStatuses::COMPLETED)
                    $projectName .= ' (Completed)';

            @endphp
            <x-menu-item :title="$projectName" class="font-bold text-xl"/>
            <x-menu-item :title="'User Role: ' . $role_name" class="font-semibold text-sm"/>
            <x-menu-item title="Index" icon="o-inbox" link="{{route('project.show',$project->id)}}" route="project.show"/>
            <x-menu-item title="Today" icon="o-check-badge" link="{{route('project.today',$project->id)}}" route="project.today"/>
            <x-menu-item title="Next 7 days" icon="o-calendar-days"  link="{{route('project.next-7-days',$project->id)}}" route="project.next-7-days"/>
            <x-menu-item title="Calendar" icon="o-calendar-date-range" link="{{route('project.calendar',$project->id)}}" route="project.calendar" no-wire-navigate/>
            <x-menu-item title="Labels" icon="o-tag"  link="{{route('project.labels',$project->id)}}" route="project.labels" />
            <x-menu-item title="Stats" icon="o-chart-bar" link="{{route('project.stats',$project->id)}}" route="project.stats" />
            <x-menu-item title="Notes" icon="o-book-open" link="{{route('project.notes',$project->id)}}" route="project.notes"/>
            <x-menu-item title="Members" icon="o-user-group" link="{{route('project.members',$project->id)}}" route="project.members"/>
            @can('editProject', $project)
                <x-custom-menu-sub title="Actions" icon="o-adjustments-vertical" open>
                    @can('seeProjectActivity', $project)
                        @include('livewire.project.project-activity-trigger')
                    @endcan
                    @can('invite',$project)
                        <x-menu-item title="Invite People" icon="o-user-group" x-on:click="$dispatch('inviting-modal-open', { project: {{$project->id}} })" />
                    @endcan
                    @can('complete',$project)
                        @include('livewire.project.complete-project-trigger')
                    @endcan
                    @can('delete',$project)
                        @include('livewire.project.delete-project-trigger')
                    @endcan
                </x-custom-menu-sub>
            @endcan
        </x-menu>
    </x-slot:sidebar>
</div>
