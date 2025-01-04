<?php

use App\Models\Comment;
use App\Traits\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Reactive;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use OwenIt\Auditing\Events\AuditCustom;

new class extends Component
{
    //
    use Toast;
    // my traits
    use HandlesAuthorization;

    #[Reactive]
    public $task;

    public $comment;
    public bool $showAddForm = false;

    protected $rules = [
        'comment' => 'required|string|max:255',
    ];

    public function addComment()
    {
        $this->validate();
        $task = clone $this->task;
        $message = $this->authorizeOrFail('create', [Comment::class, $task]);
        if ($message !== null) {
            $this->error($message);
            return;
        }

        $comment = new Comment(['content' => $this->comment]);
        $comment->user()->associate(auth()->user());
        $comment->task()->associate($this->task);
        $comment->save();

        $this->task->auditEvent = 'updated';
        $this->task->isCustomEvent = true;
        $this->task->auditCustomOld = [];
        $this->task->auditCustomNew = [
            'comment_id' => $comment->id,
        ];
        Event::dispatch(AuditCustom::class, [$this->task]);

        $this->comment = '';
        $this->showAddForm = false;
        $this->success('Comment added successfully');
        $this->dispatch('comment-added');
    }

    public function mount(): void
    {
        if (!isset($this->task))
            return;
    }
}
?>

<div class="w-full">
    <x-button label="Add comment" icon='o-chat-bubble-left-ellipsis'
        x-show="!$wire.showAddForm" x-on:click="$wire.showAddForm = true;"/>
    <x-form wire:submit="addComment" no-separator x-show="$wire.showAddForm">
        <x-input label="Comment" wire:model="comment" />
        <x-slot:actions>
            <x-button label="Cancel" icon="o-x-mark" responsive @click="$wire.showAddForm = false;"/>
            <x-button label="Add" icon="o-plus" responsive class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>

    </x-form>
</div>
