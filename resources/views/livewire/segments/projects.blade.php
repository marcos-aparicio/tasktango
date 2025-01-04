<?php

use App\Enums\TaskStatuses;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new
    #[Layout('layouts.main')]
    class extends Component
    {
        use Toast;

        public Collection $projects;

        public function mount(): void
        {
            $this->readProjects();
        }

        #[On('reload-projects')]
        public function readProjects(): void
        {
            $this->projects = auth()->user()->projects()->get();
        }
    };
?>
<div class="flex flex-col gap-4 h-full">
    <div class="sticky top-0 z-50 bg-base-100 py-4">
        <h2 class="font-extrabold text-2xl text-secondary text-center leading-tight">My Projects</h2>
        @if ($projects->count())
            <h3 class="text-lg text-secondary text-center leading-tight pb-4">{{ $projects->count() }}</h3>
        @endif
    </div>
    <div class="flex-1 overflow-y-scroll flex gap-4 flex-wrap justify-around">
        @forelse ($projects as $project)
        <a class="card bg-base-100 image-full w-full md:w-96 h-fit shadow-xl hover:opacity-85 hover:cursor-pointer" href="{{route('project.show',$project->id)}}">
            <figure>
                <img src="https://picsum.photos/500/200?blur&random={{$project->id}}" class="object-fill w-full" />
            </figure>
            <div class="card-body">
                <h2 class="card-title text-3xl line-clamp-1">{{$project->name}}</h2>
                <p class="font-secondary text-sm line-clamp-2">{{$project->description}}</p>
                <div class="flex gap-2 items-center">
                    <p>
                    @php
                    $owner = $project->owner->id === auth()->id() ? 'you' : $project->owner->username;
                    @endphp
                    Owned by {{$owner}}
                    </p>
                    @if($project->status === App\Enums\TaskStatuses::COMPLETED)
                        <span class="badge badge-success">Completed</span>
                    @endif
                </div>
            </div>
        </a>
        @empty
        <div class="my-4 flex flex-col items-center w-full gap-4">
            <span class="text-2xl font-bold">🤷 It seems empty around here</span>
            <x-button class="btn-primary" label="To create some projects click here"
                @click="$dispatch('new-project-modal')" />
        </div>
        @endforelse
    </div>
    {{-- task modal ready --}}
    <livewire:task.modal />
</div>
