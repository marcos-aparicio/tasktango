<?php

use App\Models\Comment;
use App\Models\Project;
use App\Traits\HandlesAuthorization;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Modelable;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast;
    // my traits
    use HandlesAuthorization;

    #[Locked]
    public Comment $comment;

    public bool $showUpdateForm = false;

    public string $content = '';

    public function mount()
    {
        $this->content = $this->comment->content;
    }

    public function updateComment(): void
    {
        $this->validate([
            'content' => ['required', 'string', 'max:255'],
        ]);
        $message = $this->authorizeOrFail('update', $this->comment);
        if ($message !== null) {
            $this->error($message);
            return;
        }

        $this->comment->update(['content' => $this->content]);
        $this->dispatch('comment-updated');
        $this->success('Comment updated successfully');
        $this->showUpdateForm = false;
    }

    public function deleteComment(): void
    {
        $message = $this->authorizeOrFail('delete', $this->comment);
        if ($message !== null) {
            $this->error($message);
            return;
        }
        $this->comment->delete();
        $this->success('Comment deleted successfully');
        $this->dispatch('comment-deleted');
    }
};

?>
<div class="p-4 py-1 rounded-xl flex gap-4 w-full">
    <x-avatar-or-icon
        :user="$comment->user" avatar-class="!w-10 !h-10"
        :force-to-show-icon="isset($comment->task->project) && !$comment->task->project->users->contains($comment->user)"
        icon-class="!w-10 !h-10 text-primary border-primary border-2 p-2 rounded-full"
    />
    <div class="bg-base-200 p-2 rounded-lg flex-1 gap-4">
        <div class="flex justify-between flex-1">
            <p class="font-bold text-sm">
                {{$comment->userFullName}}
            </p>
            <div class="flex gap-2">
                <p class="text-xs">{{$comment->created_at->diffForHumans()}}</p>
                @canany(['update','delete'], $comment)
                    <x-dropdown icon="o-ellipsis-vertical" class="btn-xs">
                        <x-menu-item title="Update" @click="$wire.showUpdateForm = true;"/>
                        <x-menu-item title="Delete" wire:click="deleteComment"/>
                    </x-dropdown>
                @endcanany
            </div>
        </div>
        <p class="flex-1 text-sm max-h-24 break-all overflow-y-scroll" x-show="!$wire.showUpdateForm">{{$comment->content}}</p>
        <x-form class="flex gap-1" wire:submit="updateComment" x-show="$wire.showUpdateForm">
            <x-input wire:model="content" class="input-sm"/>
            <div>
                <x-button label="Cancel" class="btn-xs" @click="$wire.showUpdateForm = false;"/>
                <x-button label="Submit" class="btn-xs btn-primary" type="submit"/>
            </div>
        </x-form>
    </div>
</div>
