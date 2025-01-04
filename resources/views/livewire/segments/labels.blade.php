<div class="flex flex-col gap-4 h-full bg-base-100">
    <span class="sticky top-0 z-10 py-2">
        <h2 class="font-extrabold text-2xl text-secondary text-center leading-tight">{{$title}}</h2>
        @if($labels->count())
        <h3 class="text-lg text-secondary text-center leading-tight pb-4">{{$labels->count()}}</h3>
        @endif
    </span>
    <div class="flex-1 overflow-y-scroll">
        <x-menu>

            @forelse ($labels as $label)
                <x-menu-item :title="$label->name" icon="o-tag" :link="isset($project) ? route('project.label',[$project->id,$label->id ]) : route('label',$label->id)" wire:navigate :badge="$label->tasks()->pending()->count()"/>
            @empty
            <div class="my-4 flex flex-col items-center w-full">
                @if(isset($project) && $project->status == App\Enums\TaskStatuses::COMPLETED)
                    <span class="text-2xl font-bold">🎉 Congratulations!</span>
                    <span>You've completed all tasks in this project</span>
                @else
                    <span class="text-2xl font-bold">🤷 It seems empty around here</span>
                    <span>Add labels to your tasks!</span>
                @endif
            </div>

            @endforelse
        </x-menu>
    </div>
    {{-- task modal ready --}}
    <livewire:task.modal />
</div>
