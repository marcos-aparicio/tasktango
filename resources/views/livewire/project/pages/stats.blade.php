<?php

use App\Models\Project;
use App\Models\User;
use App\Traits\HandlesAuthorization;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new
    #[Layout('layouts.project')]
    class extends Component
    {
        use HandlesAuthorization;

        public Project $project;

        public int $userId;

        public $users;

        public array $stats;

        // TODO: adding total notes added to the stats
        public function updatedUserId()
        {
            if ($this->userId !== auth()->id()) {
                $message = $this->authorizeOrFail('seeUserStats', $this->project);
                if ($message) {
                    $this->userId = auth()->id();
                    return;
                }
            }

            $user = User::find($this->userId);
            $project = $this->project;
            $totalUsers = $this->project->users->count();

            $userAssignedTasksCount = $project
                ->tasks
                ->filter(fn($task) => $task->assignee?->is($user) ?? false)
                ->count();
            $averageAssignedTasksCount = round(
                $project
                    ->tasks
                    ->filter(fn($task) => $task->assignee ?? false)
                    ->count() / $totalUsers,
                2
            );

            $userCreatedTasksCount = $project
                ->tasks
                ->filter(fn($task) => $task->creator?->is($user) ?? false)
                ->count();
            $averageCreatedTasksCount = round(
                $project
                    ->tasks
                    ->filter(fn($task) => $task->creator ?? false)
                    ->count() / $totalUsers,
                2
            );

            $userCompletedTasksCount = $project
                ->tasks
                ->filter(fn($task) => $task->status === \App\Enums\TaskStatuses::COMPLETED && $task->creator?->is($user) ?? false)
                ->count();
            $averageCompletedTasksCount = round(
                $project
                    ->tasks
                    ->filter(fn($task) => $task->status === \App\Enums\TaskStatuses::COMPLETED)
                    ->count() / $totalUsers,
                2
            );

            $userCommentsCount = $project
                ->tasks
                ->sum(
                    fn($task) => $task
                        ->comments
                        ->filter(fn($comment) => $comment->user?->is($user) ?? false)
                        ->count()
                );
            $averageCommentsCount = round(
                $project
                    ->tasks
                    ->sum(
                        fn($task) => $task
                            ->comments
                            ->filter(fn($comment) => $comment->user ?? false)
                            ->count()
                    )
                    / $totalUsers,
                2
            );

            $userNotesCount = $project
                ->notes
                ->filter(fn($note) => $note->author?->is($user) ?? false)
                ->count();
            $averageNotesCount = round(
                $project
                    ->notes
                    ->filter(fn($note) => $note->author ?? false)
                    ->count()
                    / $totalUsers,
                2
            );

            $this->stats = [
                [
                    'icon' => 'o-user',
                    'title' => 'Assigned Tasks',
                    'value' => $userAssignedTasksCount,
                    'average' => $averageAssignedTasksCount,
                ],
                [
                    'icon' => 'o-chat-bubble-bottom-center-text',
                    'title' => 'Comments',
                    'value' => $userCommentsCount,
                    'average' => $averageCommentsCount,
                ],
                [
                    'icon' => 'o-plus-circle',
                    'title' => 'Created Tasks',
                    'value' => $userCreatedTasksCount,
                    'average' => $averageCreatedTasksCount,
                ],
                [
                    'icon' => 'o-check-circle',
                    'title' => 'Completed Tasks',
                    'value' => $userCompletedTasksCount,
                    'average' => $averageCompletedTasksCount,
                ],
                [
                    'icon' => 'o-pencil-square',
                    'title' => 'Notes written',
                    'value' => $userNotesCount,
                    'average' => $averageNotesCount,
                ]
            ];
        }

        public function mount()
        {
            $this->userId = isset($this->userId) ? $this->userId : auth()->user()->id;
            $this->updatedUserId();
        }
    };

?>
@php
    $users = $project->users->map(function($user) {
        $output = [
            'id' => $user['id'],
            'name' => $user['username'],
        ];
        if($user['id'] === auth()->id()) {
            $output['name'] .= ' (You)';
        }
        return $output;
    })->toArray();
@endphp
<div class="flex flex-col gap-8 h-full overflow-hidden flex-1 min-h-svh">
    <h2 class="font-extrabold text-2xl text-secondary text-center leading-tight sticky top-5 z-10 p-4">Statistics</h2>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col md:flex-row gap-4 items-center justify-center">
            @can('seeUserStats', $project)
                <x-select label="Selected user" icon="o-user" :options="$users" wire:model.live="userId" inline/>
            @endcan
            <p class="text-center opacity-50">Averages are calculated based on your team members' stats</p>
        </div>
        @php
            $user = $project->users->find($userId);
            $joined_at = $user->pivot->created_at->format('h:i A l, d M Y');
            if($user->id === auth()->id()) {
                $text = 'Your';
            } else {
                $text = $user->full_name . "'s";
            }
        @endphp
        <h2 class="font-extrabold text-xl text-secondary text-center leading-tight sticky top-5 z-10 p-4">{{$text}} Stats</h2>
        <p class="text-lg text-center text-secondary text-opacity-50">Date Joined {{$joined_at}}</p>
        <div class="flex gap-4 w-full flex-wrap justify-evenly">
        @foreach($stats as $stat)
            <div class="gap-4 p-4 bg-base-300 shadow-xl rounded-xl w-full flex flex-col md:w-fit md:grid md:grid-cols-6" wire:key="{{Str::random(20)}}">
                <x-icon :name="$stat['icon']" class="w-16 h-16 text-primary col-span-2 self-center"/>
                <div class="grid grid-cols-2 gap-2 col-span-4">
                    <div class="flex flex-col justify-between">
                        <span class="text-sm opacity-50 font-thin">{{$stat['title']}}</span>
                        <span class="font-bold text-2xl">{{$stat['value']}}</span>
                    </div>
                    <div class="flex flex-col justify-between">
                        <span class="text-sm opacity-50 font-thin">Avg {{$stat['title']}}</span>
                        <span class="font-bold text-2xl">{{$stat['average']}}</span>
                    </div>
                </div>
            </div>
        @endforeach
        </div>
    </div>
</div>
