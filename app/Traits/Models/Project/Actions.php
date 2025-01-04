<?php
namespace App\Traits\Models\Project;

use App\Enums\ProjectUserRoles;
use App\Enums\TaskFrequencies;
use App\Enums\TaskStatuses;
use App\Models\Comment;
use App\Models\Label;
use App\Models\NoteAttachment;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\ProjectNote;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Events\AuditCustom;

/**
 * Trait Actions for User model
 * @package App\Traits\Models\Project
 */
trait Actions
{
    public function changeUserRole(User $user, bool $upgrade = false): void
    {
        $projectUser = $this->users->find($user);
        if ($projectUser->pivot->role === ProjectUserRoles::OWNER) {
            return;
        }

        $previousRole = $projectUser->pivot->role;
        $projectUser->pivot->role = $projectUser->pivot->role == ProjectUserRoles::MANAGER->value && !$upgrade
            ? ProjectUserRoles::COLLABORATOR
            : ProjectUserRoles::MANAGER;

        $projectUser->pivot->save();

        $this->auditEvent = "updated,user-role,{$projectUser->id}";
        $this->isCustomEvent = true;
        $this->auditCustomOld = ['role' => $previousRole];
        $this->auditCustomNew = ['role' => $projectUser->pivot->role];
        Event::dispatch(AuditCustom::class, [$this]);
    }

    /**
     * Returns a string version of the role of the user in the project
     * which is an instance of enum ProjectUserRoles
     */
    public function getRoleName(User $user): string
    {
        $projectUser = $this->users->find($user);
        return ProjectUserRoles::tryFrom($projectUser->pivot->role->value ?? $projectUser->pivot->role)?->name ?? 'Unknown';
    }

    public function getRoleNameByInt($role): string
    {
        return ProjectUserRoles::tryFrom($role->value ?? $role)?->name ?? 'Unknown';
    }

    public function createNote($content, $attachment): void
    {
        $projectUser = $this->users->find(auth()->user());
        $projectNote = new ProjectNote();
        $projectNote->project()->associate($this);
        $projectNote->content = $content;
        $projectNote->author()->associate(auth()->user());
        $projectNote->save();

        if ($attachment instanceof UploadedFile) {
            $noteAttachment = new NoteAttachment();
            $noteAttachment->path = $attachment->store('notes');
            $noteAttachment->name = $attachment->getClientOriginalName();
            $noteAttachment->note()->associate($projectNote);
            $noteAttachment->save();
        }

        $this->auditEvent = "created,note,{$projectNote->id}";
        $this->isCustomEvent = true;
        Event::dispatch(AuditCustom::class, [$this]);
    }

    /**
     * Updates the content of a project note and optionally replaces its attachment
     * if an attachment is passed and the note already has an attachment.
     *
     * @param ProjectNote $note The note to be updated.
     * @param string $content The new content for the note.
     * @param UploadedFile|null $attachment The new attachment for the note, if any.
     *
     * @return void
     */
    public function updateNote(ProjectNote $note, string $content, UploadedFile|null $attachment): void
    {
        $contentChanged = true;
        $attachmentChanged = false;
        if ($note->content === $content)
            $contentChanged = false;

        $note->content = $content;
        $note->lastEditor()->associate(auth()->user());
        $note->save();

        if ($attachment == null && $note->attachment != null) {
            $attachmentChanged = true;
            $note->attachment->delete();
            Storage::delete($note->attachment->path);
            $note->save();
        }

        if ($attachment instanceof UploadedFile) {
            $attachmentChanged = true;
            $noteAttachment = $note->attachment;
            if ($noteAttachment) {
                $noteAttachment->delete();
            }
            $noteAttachment = new NoteAttachment();
            $noteAttachment->path = $attachment->store('notes');
            $noteAttachment->name = $attachment->getClientOriginalName();
            $noteAttachment->note()->associate($note);
            $noteAttachment->save();
        }

        if ($contentChanged || $attachmentChanged) {
            $this->auditEvent = "updated,note,{$note->id}";
            $this->isCustomEvent = true;
            $this->auditCustomOld = [
                'text' => null,
                'attachment' => null,
                'is_pinned' => null
            ];
            $this->auditCustomNew = [
                'text' => intval($contentChanged),
                'attachment' => intval($attachmentChanged),
                'is_pinned' => null
            ];
            Event::dispatch(AuditCustom::class, [$this]);
        }
    }

    public function complete(): void
    {
        $this->status = TaskStatuses::COMPLETED;
        // mark everything as completed
        $this->tasks()->each(function ($task) {
            if ($task->frequency !== TaskFrequencies::NONE)
                $task->frequency = TaskFrequencies::NONE;

            $task->status = TaskStatuses::COMPLETED;
            $task->save();
        });
        $this->save();

        $this->auditEvent = "completed,project,{$this->id}";
        $this->isCustomEvent = true;
        Event::dispatch(AuditCustom::class, [$this]);
    }

    /**
     * Assuming user is valid and within the project
     */
    public function removeUser(User $user): void
    {
        $projectUser = $this->users->find($user);
        $this->users()->detach($projectUser);
        // setting assignee to null for all tasks assigned to the user
        $this->tasks()->whereHas('assignee', function ($query) use ($user) {
            $query->where('id', $user->id);
        })->each(function ($task) {
            $task->assignee()->dissociate();
            $task->save();
        });
        $this->auditEvent = "deleted,user,{$projectUser->id}";
        $this->isCustomEvent = true;
        Event::dispatch(AuditCustom::class, [$this]);
    }
}
