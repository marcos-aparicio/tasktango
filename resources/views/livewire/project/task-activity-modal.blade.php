<?php

use App\Models\Task;
use App\Traits\HandlesAuthorization;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;
    // my traits
    use HandlesAuthorization;

    public ?Task $task;
    public bool $openTaskModal = false;

    #[On('show-task-activity-modal')]
    public function showModal(Task $task): void
    {
        $message = $this->authorizeOrFail('seeTaskActivity', $task->project);
        if ($message) {
            $this->error($message);
            return;
        }
        $this->openTaskModal = true;
        $this->task = $task;
    }
};

?>
@php
    $propertyMapper = [
        'name' => 'Name',
        'description' => 'Description',
        'due_date' => 'Due Date',
        'priority' => 'Priority',
        'frequency' => 'Frequency',
        'status' => 'Status',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'order' => 'Order',
    ];
@endphp

<x-modal boxClass="max-w-3xl flex flex-col pt-0 px-0" wire:model="openTaskModal">
    {{-- modal's header --}}
    <div class="flex justify-between p-4 pb-2">
    @isset($task)
        <p>Task Activity for Task "{{$task->name}}"</p>
    @endisset
    </div>
    <div class="divider divider-primary my-0 h-fit"></div>
    {{-- modal's body --}}
@isset($task)
    <div class="flex flex-col gap-4 p-4">
        @forelse($task->audits->reject(fn($audit) => $audit->event === 'created')->sortByDesc('created_at') as $audit)
            <div class="flex flex-col gap-2 break-all">
                <ul>
                    @foreach($audit->getModified() as $attribute => $modified)
                    @php
                        $auditAuthor = $audit->user->username ?? "Deleted user";
                        if(!$task->project->users->contains($audit->user) && isset($audit->user)){
                            $auditAuthor = 'Previous Member';
                        }
                    @endphp

                    @switch($attribute)
                        @case('subtask_id')
                            @if(!isset($modified['old']))
                                @php
                                    $subtask = \App\Models\Task::find($modified['new'])->name ?? 'N/A';
                                @endphp
                                <li>
                                    <strong class="text-primary">{{$subtask}}</strong>
                                    was added as a subtask
                                    (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                                </li>
                            @elseif(!isset($modified['new']))
                                @php
                                    $subtask = \App\Models\Task::find($modified['old'])->name ?? 'N/A';
                                @endphp
                                <li>
                                    Subtask
                                    <strong class="text-primary">{{$subtask}}</strong>
                                    was removed
                                    (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                                </li>
                            @endif
                            @break

                        @case('comment_id')
                            @php
                                $comment = \App\Models\Comment::find($modified['new']);
                                $commentAuthor = $comment->userUsername ?? 'N/A';
                            @endphp
                            <li>
                                <strong class="text-primary">{{$commentAuthor}}</strong>
                                added a comment to the task
                            </li>
                            @break

                        @case('labels')
                            @php
                                $newLabelIds = collect($modified['new'])->pluck('id')->toArray();
                                $oldLabelIds = collect($modified['old'])->pluck('id')->toArray();
                                $newLabels = \App\Models\Label::whereIn('id', $newLabelIds)
                                        ->where('project_id',$task->project->id)
                                        ->pluck('name')->toArray();
                                $oldLabels = \App\Models\Label::whereIn('id', $oldLabelIds)
                                           ->where('project_id',$task->project->id)
                                           ->pluck('name')->toArray();
                            @endphp
                            @if(empty($oldLabels) && empty($newLabels))
                            @else
                            <li>
                                Labels changed from
                                <strong class="text-primary">{{ empty($oldLabels) ? 'No labels' : implode(', ', $oldLabels) }}</strong>
                                to <strong class="text-primary">{{ empty($newLabels) ? 'No labels' : implode(', ', $newLabels) }}</strong>
                                (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                            </li>
                            @endif
                            @break

                        @case('assignee_user_id')
                            @php
                            //TODO: modify the assignee user id to be the new accessor
                                $newAssignee = $modified['new'] ? \App\Models\User::find($modified['new'])->username ??  'N/A' : 'N/A';
                                if(!$task->project->users->contains($modified['new']) && isset($modified['new'])){
                                    $newAssignee = 'Previous Member';
                                }

                                $oldAssignee = $modified['old'] ? \App\Models\User::find($modified['old'])->username ??  'N/A' : 'N/A';
                                if(!$task->project->users->contains($modified['old']) && isset($modified['old'])){
                                    $oldAssignee = 'Previous Member';
                                }
                            @endphp
                            <li>
                                <strong class="text-primary">Assignee</strong>
                                was changed from
                                <strong class="text-primary">{{$oldAssignee}}</strong>
                                to <strong class="text-primary">{{$newAssignee}}</strong>
                                (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                            </li>
                            @break

                        @case('parent_task_id')
                            @php
                                $oldParent = $modified['old'] ? \App\Models\Task::find($modified['old'])->name : 'N/A';
                                $newParent = $modified['new'] ? \App\Models\Task::find($modified['new'])->name : 'N/A';
                            @endphp
                            <li>
                            Task is now a subtask of
                                <strong class="text-primary">{{$newParent}}</strong>
                                (was {{$oldParent}})
                                (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                            </li>
                            @break

                        @case('project_id')
                            @php
                                $oldProject = $modified['old'] ? \App\Models\Project::find($modified['old'])->name : 'N/A';
                                $newProject = $modified['new'] ? \App\Models\Project::find($modified['new'])->name : 'N/A';
                            @endphp
                            <li>
                                <strong class="text-primary">Project</strong>
                                was changed from
                                <strong class="text-primary">{{$oldProject}}</strong>
                                to <strong class="text-primary">{{$newProject}}</strong>
                                (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                            </li>
                            @break

                        @case('status')
                            @php
                                $oldStatus = $modified['old'] ? $modified['old']->name : 'N/A';
                                $newStatus = $modified['new'] ? $modified['new']->name : 'N/A';
                            @endphp
                            <li>
                                Status was changed from
                                <strong class="text-primary">{{$oldStatus}}</strong>
                                to <strong class="text-primary">{{$newStatus}}</strong>
                            </li>
                            @break

                        @case('sub_project_id')
                            @php
                                $oldSubproject = $modified['old'] ? \App\Models\SubProject::find($modified['old'])->name : 'N/A';
                                $newSubproject = $modified['new'] ? \App\Models\SubProject::find($modified['new'])->name : 'N/A';
                            @endphp
                            <li>
                                <strong class="text-primary">SubProject</strong>
                                was changed from
                                <strong class="text-primary">{{$oldSubproject}}</strong>
                                to <strong class="text-primary">{{$newSubproject}}</strong>
                                (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                            </li>
                            @break

                        @case('frequency')
                            @php
                                $oldFrequency = $modified['old'] ? $modified['old']->name : 'N/A';
                                $newFrequency = $modified['new'] ? $modified['new']->name : 'N/A';
                            @endphp
                            <li>
                                <strong class="text-primary">Frequency</strong>
                                was changed from
                                <strong class="text-primary">{{ $oldFrequency }}</strong>
                                to <strong class="text-primary">{{ $newFrequency }}</strong>
                                (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                            </li>
                            @break

                        @default
                            <li>
                                <strong class="text-primary">{{ $propertyMapper[$attribute] ?? $attribute }}</strong>
                                was changed from
                                <strong class="text-primary">{{ $modified['old'] ?? 'N/A' }}</strong>
                                to <strong class="text-primary">{{ $modified['new'] ?? 'N/A' }}</strong>
                                (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                            </li>
                    @endswitch
                    @endforeach
                </ul>
                <span class="text-sm opacity-50">{{ $audit->created_at->diffForHumans() }}</span>
            </div>
              <div class="divider m-0 p-0"></div>
        @empty
            <div class="flex gap-2">
                <span>No activity yet</span>
            </div>
        @endforelse
    </div>
@endisset
</x-modal>
