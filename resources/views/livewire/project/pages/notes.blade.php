<?php

use App\Models\NoteAttachment;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Traits\HandlesAuthorization;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Mary\Traits\Toast;
use OwenIt\Auditing\Events\AuditCustom;

enum Sorts: string
{
    case Default = 'Default';
    case Date = 'Date Descending';
    case DateAscending = 'Date Ascending';
};

new
    #[Layout('layouts.project', ['useEasyMDE' => true])]
    class extends Component
    {
        use Toast;
        // my traits
        use HandlesAuthorization;

        public Sorts $sort = Sorts::Default;

        public function updateSort(string $sort): void
        {
            $this->sort = match ($sort) {
                'date' => Sorts::Date,
                'priority' => Sorts::Priority,
                'date-asc' => Sorts::DateAscending,
                default => Sorts::Default,
            };
            $this->updateNotes();
        }

        #[Locked]
        public Project $project;

        #[Locked]
        public Collection $notes;

        #[On('refresh-notes')]
        public function refreshNotes(): void
        {
            $this->project->refresh();
            $this->updateNotes();
        }

        public function updateNotes()
        {
            $this->notes = match ($this->sort) {
                Sorts::Date => $this->project->notes->sortByDesc('created_at'),
                Sorts::DateAscending => $this->project->notes->sortBy('created_at'),
                default => $this->project->notes->sortBy([
                    ['is_pinned', 'desc'],
                    ['created_at', 'desc']
                ]),
            };
        }

        public function mount()
        {
            $this->updateNotes();
        }

        public function deleteNote(ProjectNote $note): void
        {
            $message = $this->authorizeOrFail('delete', [ProjectNote::class, $note, $this->project]);
            if ($message !== null) {
                $this->error(title: $message);
                return;
            }
            $note->delete();

            $this->project->auditEvent = "deleted,note,{$note->id}";
            $this->project->isCustomEvent = true;
            Event::dispatch(AuditCustom::class, [$this->project]);

            $this->dispatch('refresh-notes');
            $this->success(title: 'Note deleted successfully');
        }

        public function editNote(ProjectNote $note): void
        {
            $message = $this->authorizeOrFail('update', [ProjectNote::class, $note, $this->project]);
            if ($message !== null) {
                $this->error(title: $message);
                return;
            }
            $this->dispatch('project-note-modal', $note->id);
        }

        public function togglePinInNote(ProjectNote $note): void
        {
            $message = $this->authorizeOrFail('update', [ProjectNote::class, $note, $this->project]);
            if ($message !== null) {
                $this->error(title: $message);
                return;
            }
            $previousPin = $note->is_pinned;
            $note->update(['is_pinned' => !$note->is_pinned]);
            $note->save();
            $note->project->auditEvent = "updated,note,{$note->id}";
            $note->project->isCustomEvent = true;
            $note->project->auditCustomOld = ['is_pinned' => intval($previousPin), 'text' => null, 'attachment' => null];
            $note->project->auditCustomNew = ['is_pinned' => intval(!$previousPin), 'text' => null, 'attachment' => null];
            Event::dispatch(AuditCustom::class, [$note->project]);

            $this->dispatch('refresh-notes');
            $this->success(title: !$note->is_pinned ? 'Note unpinned successfully' : 'Note pinned successfully');
        }

        public function downloadAttachment(NoteAttachment $attachment)
        {
            $message = $this->authorizeOrFail('downloadAttachment', [ProjectNote::class, $this->project, $attachment]);
            if ($message !== null) {
                $this->error(title: $message);
                return;
            }
            return Storage::download($attachment->path, $attachment->name);
        }
    };
?>
<div class="flex flex-col gap-8 h-full overflow-hidden flex-1 min-h-svh">
    <h2 class="font-extrabold text-2xl text-secondary text-center leading-tight p-4">📓 Notes</h2>
    <div class="flex justify-between">
        @can('create', [ProjectNote::class, $project])
            <x-button icon="o-plus" label="New note 📝" @click="$wire.dispatch('project-note-modal')" class="w-fit ml-0 btn-primary"/>
        @endcan
        @php
            $title = $sort->value;
        @endphp
        <x-dropdown :label="'Sort by: '.$title" class="btn-ghost btn-sm">
            <x-menu-item title="Default" wire:click="updateSort('')" />
            <x-menu-item title="Due Date (Descending)" wire:click="updateSort('date')"/>
            <x-menu-item title="Due Date (Ascending)" wire:click="updateSort('date-asc')"/>
        </x-dropdown>
    </div>
    <div @class(['grid auto-cols-fr gap-4 grid-cols-1', $project->notes->count() <= 1? 'md:grid-cols-1' : 'md:grid-cols-2'])>
        @forelse($notes as $note)
            <div class="flex flex-col gap-2 p-4 bg-base-300 rounded-lg shadow-md items-center max-h-96" wire:key="note-{{$note->id}}">
                <div class="flex justify-between items-center w-full">
                    <div class="flex gap-4">
                        <h3 class="font-bold text-xs text-secondary">Note #{{ $note->id }}</h3>
                        <span class="text-xs text-primary">Author: {{ $note->authorUsername }}</span>
                        @if($note->is_pinned)
                            <x-icon name="fluentui.pin-32" class="text-primary w-5 text-xs" label="Pinned"/>
                        @endif
                        @isset($note->last_editor_id)
                            <x-icon name="o-hashtag" class="opacity-50 w-2 text-xs" label="Edited"/>
                        @endisset
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400">{{ $note->created_at->diffForHumans() }}</span>
                        @canany(['update', 'delete','view'], [ProjectNote::class, $note, $project])
                            <x-dropdown icon="o-ellipsis-vertical" class="btn-ghost" right>
                                @can('view', [ProjectNote::class, $note, $project])
                                    <x-menu-item label="Open" icon="o-arrow-top-right-on-square" link="{{route('project.note',[$project,$note])}}"/>
                                @endcan
                                @can('update', [ProjectNote::class, $note, $project])
                                    <x-menu-item label="Edit" icon="o-pencil" wire:click="editNote({{ $note->id }})"/>
                                    @php
                                        if($note->is_pinned){
                                            $icon = 'fluentui.pin-off-32-o';
                                            $label = 'Unpin';
                                        } else{
                                            $icon = 'fluentui.pin-32-o';
                                            $label = 'Pin';
                                        }
                                    @endphp
                                    <x-menu-item :$label :$icon wire:click="togglePinInNote({{ $note->id }})"/>
                                @endcan
                                @can('delete', [ProjectNote::class, $note, $project])
                                    <x-menu-item label="Delete" icon="o-trash" wire:click="deleteNote({{ $note->id }})"/>
                                @endcan
                            </x-dropdown>
                        @endcanany
                    </div>
                </div>
                <div class="prose-sm prose-a:text-primary prose-ol:list-decimal w-full h-full break-all text-pretty overflow-y-scroll bg-base-100 p-4 rounded">
                    {!! Str::of($note->content)->markdown() !!}
                </div>
                @isset($note->attachment)
                    <x-button icon="o-arrow-down-tray" :label="$note->attachment->name" class="btn-secondary btn-xs mr-auto overflow-hidden" wire:key="{{$note->id}}-{{$note->attachment->id}}" wire:click="downloadAttachment({{$note->attachment->id}})"/>
                @endisset
            </div>
        @empty
            <span>No notes (for now)</span>
        @endforelse
    </div>

    <livewire:project.note-modal :$project/>
</div>
