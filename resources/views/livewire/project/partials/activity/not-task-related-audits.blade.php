<div class="flex flex-col gap-4 p-4">
    @forelse($project->audits->reject(fn($audit)=>!isset($audit->user_id))->sortByDesc('created_at') as $audit)
    @php

        $elements = explode(",",$audit->event);
        $event = $elements[0] ?? null;
        $model = $elements[1] ?? null;
        $id = $elements[2] ?? null;
        $auditAuthor = $audit->user->username ?? "Deleted user";
        if(!$project->users->contains($audit->user))
            $auditAuthor = "Previous Member";

    @endphp
        @if(!isset($model))
        <ul>
            <li>Creating Project:</li>
            @foreach($audit->getModified() as $attribute => $modified)
            @if($attribute === 'updated_at' || $attribute === 'created_at' || $attribute === 'id' || $attribute === 'status')
                @continue
            @endif
            <li>
                {{ $propertyMapper[$attribute] ?? $attribute }}:
                <strong class="text-primary">{{ $modified['new'] ?? 'N/A' }}</strong>
            </li>
            @endforeach
        </ul>
        @elseif($event === 'created' && $model === 'user-role')
            @php
                $userName = $project->users->find($id)?->username ?? 'Previous Member';
            @endphp
            <span>
                User <strong class="text-primary">{{$userName}}</strong> was added to the project
            </span>
            <span class="text-sm opacity-50">{{ $audit->created_at->diffForHumans() }}</span>
            <div class="divider m-0 p-0"></div>
            @continue
        @elseif($event === 'completed' && $model === 'project')
            <span>
                Project was marked as completed
            </span>
        @elseif($event === 'updated' && $model === 'user-role')
            @php
                $changedUserUsername = $project->users->find($id)?->username ?? 'Previous Member';
                $oldRole = $project->getRoleNameByInt($audit->getModified()['role']['old']);
                $newRole = $project->getRoleNameByInt($audit->getModified()['role']['new']);
            @endphp
            <span>
                <strong class="text-primary">{{ $changedUserUsername }}</strong>
                role was changed from
                <strong class="text-primary">{{$oldRole}}</strong>
                to <strong class="text-primary">{{$newRole}}</strong>
            </span>
        @elseif($event === 'deleted' && $model === 'invitation')
            @php
                $inviteeUsername = \App\Models\User::find($audit->getModified()['invitee_id']['old'])
                    ->username ?? 'Deleted User';
            @endphp
            <span>
                Invitation to <strong class="text-primary">{{$inviteeUsername}}</strong> was canceled
            </span>
        @elseif($event === 'deleted' && $model === 'user')
            @php
                $deletedUserUsername = App\Models\User::find($id)->username ?? 'Deleted User';
            @endphp
            <span>
                Member <strong class="text-primary">{{$deletedUserUsername}}</strong> was removed from the project
            </span>
        @elseif($event === 'deleted' && $model === 'note')
            <span>
                <strong class="text-primary">Note #{{$id}}</strong> was deleted
            </span>
        @elseif($event === 'created' && $model === 'note')
            <span>
                <strong class="text-primary">Note #{{$id}}</strong> was created
            </span>
        @elseif($event === 'created' && $model === 'invitation')
            @php
                $inviteeUsername = \App\Models\User::find($audit->getModified()['invitee_id']['new'])
                    ->username ?? 'Deleted User';
            @endphp
            <span>
                User <strong class="text-primary">{{$inviteeUsername}}</strong> was invited to join the project
            </span>
        @elseif($event === 'updated' && $model === 'note')
            @php
                $id = $project->notes?->find($id)?->id;
                if(!isset($id))
                    $noteID = 'Deleted Note';
                else
                    $noteID = "Note #$id";
                $whatWasUpdated = "";
                $modified = $audit->getModified();
                if($modified['text']['new'] && $modified['attachment']['new']){
                    $whatWasUpdated = "content and attachment were";
                } elseif($modified['text']['new']){
                    $whatWasUpdated = "content was";
                } elseif($modified['attachment']['new']){
                    $whatWasUpdated = "attachment was";
                }
                if($modified['is_pinned']['new'] === 1 && $modified['is_pinned']['old'] === 0){
                    $whatWasUpdated = "";
                }
                $wasPinned = "";
                if($modified['is_pinned']['new'] === 0 && $modified['is_pinned']['old'] === 1){
                    $whatWasUpdated = "";
                    $wasPinned = "un";
                }
            @endphp
            <span>
                @if($whatWasUpdated !== "")
                    <strong class="text-primary">{{$noteID}}'s</strong> {{$whatWasUpdated}} updated
                @else
                    <strong class="text-primary">{{$noteID}}</strong> was {{$wasPinned}}pinned
                @endif
            </span>
        @else

        <div>
                <span>event: <strong class="text-primary">{{$event ?? 'NA'}}</strong></span>
                <span>model: <strong class="text-primary">{{$model ?? 'NA'}}</strong></span>
                <span>ID: <strong class="text-primary">{{$id ?? 'NA'}}</strong></span>
        </div>
        @endif
        <span>(by <strong class="text-secondary">{{$auditAuthor}}</strong>)</span>
        <span class="text-sm opacity-50">{{ $audit->created_at->diffForHumans() }}</span>
        <div class="divider m-0 p-0"></div>
    @empty
        <div class="text-center text-gray-500">No Activity yet</div>
    @endforelse
</div>
