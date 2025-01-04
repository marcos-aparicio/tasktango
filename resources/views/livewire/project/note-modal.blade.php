
<?php

use App\Models\NoteAttachment;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Traits\HandlesAuthorization;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new class extends Component
{
    use Toast, WithFileUploads;
    // my traits
    use HandlesAuthorization;

    // properties
    #[Locked]
    public Project $project;

    #[Locked]
    public ?ProjectNote $note = null;

    #[Locked]
    public string $title = 'Create a note 📝';

    #[Locked]
    public string $buttonLabel = 'Create Note';

    public string $text;
    public $attachment;

    #[Locked]
    public $alreadyUploadedAttachment;

    public bool $openTaskModal = false;

    public function rules(): array
    {
        return [
            'text' => 'required|string|min:10|max:5000',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,doc,docx',
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'The note content is required',
            'text.min' => 'The note content must be at least 10 characters',
            'text.max' => 'The note content must not exceed 5000 characters',
            'attachment.file' => 'The attachment must be files',
            'attachment.mimes' => 'The attachment must be of type: pdf, doc, docx',
            'attachment.max' => 'The attachment must not exceed 10MB',
        ];
    }

    #[On('project-note-modal')]
    public function showModal(?int $noteId = null): void
    {
        $this->reset(['text', 'attachment', 'alreadyUploadedAttachment']);
        $this->resetValidation();
        if ($noteId) {
            $this->note = ProjectNote::find($noteId);
            $message = $this->authorizeOrFail('update', [$this->note, $this->project]);
            if ($message !== null) {
                $this->error('text', $message);
                return;
            }
            $this->text = $this->note->content;
            $this->title = 'Update note 📝';
            $this->alreadyUploadedAttachment = $this->note->attachment;
            $this->buttonLabel = 'Update Note';
            $this->openTaskModal = true;
            return;
        }
        $this->note = null;
        $this->text = '';
        $this->attachment = null;
        $this->title = 'Create a note 📝';
        $this->buttonLabel = 'Create Note';
        $this->openTaskModal = true;
    }

    public function createOrUpdateNote(): void
    {
        $this->validate();

        if ($this->note) {
            $message = $this->authorizeOrFail('update', [ProjectNote::class, $this->note, $this->project]);
            if ($message !== null) {
                $this->addError('text', $message);
                return;
            }
            $this->project->updateNote($this->note, $this->text, $this->attachment);
            $this->success(title: 'Note updated successfully');
        } else {
            $message = $this->authorizeOrFail('create', [ProjectNote::class, $this->project]);
            if ($message !== null) {
                $this->addError('text', $message);
                return;
            }
            $this->project->createNote($this->text, $this->attachment);
            $this->success(title: 'Note created successfully');
        }
        $this->openTaskModal = false;
        $this->reset(['note', 'text', 'attachment']);
        $this->dispatch('refresh-notes');
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

    public function deleteAttachment(NoteAttachment $attachment)
    {
        $message = $this->authorizeOrFail('deleteAttachment', [ProjectNote::class, $this->project, $attachment]);
        if ($message !== null) {
            $this->error(title: $message);
            return;
        }
        Storage::delete($attachment->path);
        // $attachment->delete();
        $this->alreadyUploadedAttachment = null;
        // $this->success(title: 'Attachment deleted successfully');
    }
};

?>

<x-modal class="z-40" boxClass="max-w-5xl flex flex-col pt-0 px-0 overflow-y-hidden" wire:model="openTaskModal">
    {{-- modal's header --}}
    <div class="flex justify-between p-4 pb-2">
        <p>{{$title}} for {{$project->name}}</p>
    </div>
    <div class="divider divider-primary my-0 h-fit"></div>
    {{-- modal's body --}}
    <x-form class="flex flex-col gap-4 p-2 overflow-y-scroll overflow-x-hidden md:p-4" wire:submit="createOrUpdateNote">
        @php
            $config = [
                'toolbar' => ['heading', 'bold', 'italic', '|', 'link','upload-image', 'preview'],
                'maxHeight' => '300px',
            ];
        @endphp
        <div class="overflow-x-scroll" wire:key="{{Str::random(25)}}">
            <x-markdown wire:model="text" label="Content" hint="Write important stuff" :$config />
        </div>
        <span>Attachment (max 1)</span>
        <div class="flex gap-2 items-center flex-wrap justify-center">
        @if(!isset($alreadyUploadedAttachment))
            <x-custom-file wire:model="attachment" input-class="max-md:file-input-xs file-input-sm w-full"/>
        @endif
        @if(isset($alreadyUploadedAttachment) && isset($note))
            <x-dropdown :label="$alreadyUploadedAttachment->name" class="btn btn-sm btn-secondary" wire:key="already-uploaded-file-{{$alreadyUploadedAttachment->id}}-file-{{$note->id}}">
                <x-menu-item title="Delete" icon="o-trash" wire:click="deleteAttachment({{$alreadyUploadedAttachment->id}})"/>
                <x-menu-item title="Download" icon="o-arrow-down-tray" wire:click="downloadAttachment({{$alreadyUploadedAttachment->id}})"/>
            </x-dropdown>
        @endif
        </div>
        <span class="text-sm opacity-50 mx-auto">Accepted filetypes are doc,docx and pdf</span>
        <x-slot:actions>
            <x-button label="Cancel" icon="o-x-mark"  @click="$wire.openTaskModal = false;" class="max-md:btn-sm"/>
            <x-button :label="$buttonLabel" icon="o-plus" class="btn-primary max-md:btn-sm" type="submit"/>
        </x-slot:actions>

    </x-form>
</x-modal>
