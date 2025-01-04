<?php

use App\Models\NoteAttachment;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Traits\HandlesAuthorization;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new
    #[Layout('layouts.project')]
    class extends Component
    {
        use Toast;
        // my traits
        use HandlesAuthorization;

        #[Locked]
        public ProjectNote $note;

        #[On('refresh-notes')]
        public function refreshNote(): void
        {
            $this->note->refresh();
        }

        public function editNote(ProjectNote $note): void
        {
            $message = $this->authorizeOrFail('update', [ProjectNote::class, $note, $this->note->project]);
            if ($message !== null) {
                $this->error(title: $message);
                return;
            }
            $this->dispatch('project-note-modal', $note->id);
        }

        public function downloadAttachment(NoteAttachment $attachment)
        {
            $message = $this->authorizeOrFail('downloadAttachment', [ProjectNote::class, $this->note->project, $attachment]);
            if ($message !== null) {
                $this->error(title: $message);
                return;
            }
            return Storage::download($attachment->path, $attachment->name);
        }
    };
?>
<div class="flex flex-col gap-8 h-full overflow-hidden flex-1 h-[85svh] md:h-[90svh]">
    <x-button icon="o-arrow-long-left" label="Go back" x-on:click="window.history.back()" class="w-fit"/>
    @if($note->is_pinned)
        <x-icon name="fluentui.pin-32" class="text-primary w-5" label="Pinned"/>
    @endif
    @isset($note->last_editor_id)
        <x-icon name="o-hashtag" class="opacity-50 w-2 text-xs" label="Edited"/>
    @endisset
    <div class="flex w-full justify-between">
        <div class="flex items-center gap-2">
            <h3 class="font-bold text-secondary">Note #{{ $note->id }}</h3>
            <span class="text-primary">Author: {{ $note->authorUsername }}</span>
        </div>

        <div class="flex items-center gap-2">
        @isset($note->attachment)
            <x-button icon="o-arrow-down-tray" :label="$note->attachment->name" class="btn-secondary btn-xs overflow-hidden" wire:key="{{$note->id}}-{{$note->attachment->id}}" wire:click="downloadAttachment({{$note->attachment->id}})"/>
        @endisset
        @canany(['update'], [ProjectNote::class, $note, $note->project])
            <x-dropdown icon="o-ellipsis-vertical" class="btn-ghost">
                @can('update', [ProjectNote::class, $note, $note->project])
                    <x-menu-item label="Edit" icon="o-pencil" wire:click="editNote({{ $note->id }})"/>
                @endcan
            </x-dropdown>
        @endcanany
        </div>
    </div>
    <div class="prose-sm prose-ol:list-decimal prose-a:text-primary  break-all text-pretty p-4 rounded overflow-y-scroll bg-base-300">
        {!! Str::of($note->content)->markdown() !!}
    </div>
    <livewire:project.note-modal :project="$note->project"/>
</div>
