{{-- Intended to use inside x-dropdown or x-menu components and assuming task component here --}}
@isset($task->project)
    @if(str_contains(url()->previous(), '/project/') || str_contains(url()->current(), '/livewire/update'))
        @can('seeTaskActivity',$task->project)
            <x-menu-item title="Activity" icon="fluentui.pulse-20" @click="$wire.dispatch('show-task-activity-modal',[{{$task->id}}])" class="z-40"/>
        @endcan
    @endif
@endisset
