<div class="flex flex-col gap-4 p-4">
@forelse($this->getTaskAudits()->sortByDesc('created_at') as $audit)
    @php
        $auditAuthor = $audit->user->username ?? "Deleted user";
        $task = $audit->auditable;
        if(!$task->project->users->contains($audit->user) && isset($audit->user))
            $auditAuthor = 'Previous Member';
        $modified = [];
        if($audit->event === 'updated')
            $modified = $audit->getModified();
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
    <div class="flex flex-col gap-2">
        @if($audit->event === 'created')
            <p>
                <strong class="text-secondary">{{$auditAuthor}}</strong>
                created task: <span class="underline text-primary">{{$task->name}}</span>
            </p>
        @elseif($audit->event === 'updated'  && ($modified['status']['new'] ?? null) === App\Enums\TaskStatuses::DELETED)
            <p>
                <strong class="text-secondary">{{$auditAuthor}}</strong>
                deleted task <span class="line-through text-primary">{{$task->name}}</span>
            </p>
        @elseif($audit->event === 'updated'  && ($modified['status']['new'] ?? null) === App\Enums\TaskStatuses::COMPLETED)
            <p>
                <strong class="text-secondary">{{$auditAuthor}}</strong>
                completed task <span class="text-primary">{{$task->name}}</span>
            </p>
        @elseif($audit->event === 'updated' || $audit->event === 'sync')
            <ul>
                    @foreach($audit->getModified() as $attribute => $modified)
                    @php
                        $auditAuthor = $audit->user->username ?? "Deleted user";
                        if(!$task->project->users->contains($audit->user) && isset($audit->user)){
                            $auditAuthor = 'Previous Member';
                        }
                    @endphp
                    <li>
                        <span>Task <strong class="text-primary">{{$task->name}}</strong></span>
                        <br/>
                        @switch($attribute)
                            @case('subtask_id')
                                @if(!isset($modified['old']))
                                    @php
                                        $subtask = \App\Models\Task::find($modified['new'])->name ?? 'N/A';
                                    @endphp
                                        <strong class="text-primary">{{$subtask}}</strong>
                                        was added as a subtask
                                        (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                                @elseif(!isset($modified['new']))
                                    @php
                                        $subtask = \App\Models\Task::find($modified['old'])->name ?? 'N/A';
                                    @endphp
                                        Subtask
                                        <strong class="text-primary">{{$subtask}}</strong>
                                        was removed
                                        (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                                @endif
                                @break

                            @case('comment_id')
                                @php
                                    $comment = \App\Models\Comment::find($modified['new']);
                                    $commentAuthor = $comment->userUsername ?? 'N/A';
                                @endphp
                                    <strong class="text-primary">{{$commentAuthor}}</strong>
                                    added a comment to the task
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
                                    Labels changed from
                                    <strong class="text-primary">{{ empty($oldLabels) ? 'No labels' : implode(', ', $oldLabels) }}</strong>
                                    to <strong class="text-primary">{{ empty($newLabels) ? 'No labels' : implode(', ', $newLabels) }}</strong>
                                    (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
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
                                    <strong class="text-primary">Assignee</strong>
                                    was changed from
                                    <strong class="text-primary">{{$oldAssignee}}</strong>
                                    to <strong class="text-primary">{{$newAssignee}}</strong>
                                    (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                                @break

                            @case('parent_task_id')
                                @php
                                    $oldParent = $modified['old'] ? \App\Models\Task::find($modified['old'])->name : 'N/A';
                                    $newParent = $modified['new'] ? \App\Models\Task::find($modified['new'])->name : 'N/A';
                                @endphp
                                Task is now a subtask of
                                    <strong class="text-primary">{{$newParent}}</strong>
                                    (was {{$oldParent}})
                                    (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                                @break

                            @case('project_id')
                                @php
                                    $oldProject = $modified['old'] ? \App\Models\Project::find($modified['old'])->name : 'N/A';
                                    $newProject = $modified['new'] ? \App\Models\Project::find($modified['new'])->name : 'N/A';
                                @endphp
                                    <strong class="text-primary">Project</strong>
                                    was changed from
                                    <strong class="text-primary">{{$oldProject}}</strong>
                                    to <strong class="text-primary">{{$newProject}}</strong>
                                    (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                                @break
                            @case('status')
                            @case('sub_project_id')
                                @php
                                    $oldSubproject = $modified['old'] ? \App\Models\SubProject::find($modified['old'])->name : 'N/A';
                                    $newSubproject = $modified['new'] ? \App\Models\SubProject::find($modified['new'])->name : 'N/A';
                                @endphp
                                    <strong class="text-primary">SubProject</strong>
                                    was changed from
                                    <strong class="text-primary">{{$oldSubproject}}</strong>
                                    to <strong class="text-primary">{{$newSubproject}}</strong>
                                    (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                                @break

                            @case('frequency')
                                @php
                                    $oldFrequency = $modified['old'] ? $modified['old']->name : 'N/A';
                                    $newFrequency = $modified['new'] ? $modified['new']->name : 'N/A';
                                @endphp
                                    <strong class="text-primary">Frequency</strong>
                                    was changed from
                                    <strong class="text-primary">{{ $oldFrequency }}</strong>
                                    to <strong class="text-primary">{{ $newFrequency }}</strong>
                                    (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                                @break

                            @default
                                <strong class="text-primary">{{ $propertyMapper[$attribute] ?? $attribute }}</strong>
                                was changed from
                                <strong class="text-primary">{{ $modified['old'] ?? 'N/A' }}</strong>
                                to <strong class="text-primary">{{ $modified['new'] ?? 'N/A' }}</strong>
                                (by <strong class="text-secondary">{{$auditAuthor}}</strong>)
                        @endswitch
                    </li>
                    @endforeach
                </ul>
        @else
        @endif
        <span class="text-sm opacity-50">{{ $audit->created_at->diffForHumans() }}</span>
        <div class="divider m-0 p-0"></div>
    </div>
@empty
    <div class="text-center text-gray-500">No Activity yet</div>
@endforelse
</div>
