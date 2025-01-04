{{-- comments, intended to use within the modal component --}}
@isset($task)
<div class="collapse collapse-plus bg-base-300 w-full" wire:key="comments-from-{{$task->id}}">
    <input type="checkbox"/>
    <p class="font-bold text-lg collapse-title">
    <span class="text-lg font-bold">Comments</span>
    @if($task->comments()->count() > 0)
        <span class="badge badge-neutral">{{ $task->comments()->count() }}</span>
    @endif
    </p>
    <div class="flex flex-col gap-2 items-end collapse-content">
        @if($task->comments()->count() > 0)
            @foreach($task->comments as $comment)
                <livewire:task.subcomps.comment wire:key="task-{{$task->id}}-comment-{{$comment->id}}" :$comment/>
            @endforeach
        @endif
        <div class="flex justify-end w-full">
            <livewire:task.add-comment :$task wire:key="add-comment-to-task-{{$task->id}}-{{Str::random(20)}}"/>
        </div>
    </div>
</div>
@endisset
