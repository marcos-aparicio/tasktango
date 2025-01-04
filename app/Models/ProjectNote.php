<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectNote extends Model
{
    use HasFactory;

    protected $fillable = ['content', 'is_pinned'];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($note) {
            $note->attachment?->delete();
        });
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function authorUsername(): Attribute
    {
        return new Attribute(
            get: function () {
                if (!$this->project->users->contains($this->author))
                    return 'Previous Member';

                if ($this->author->is(auth()->user()))
                    return 'You';

                return $this->author->username;
            }
        );
    }

    public function lastEditor()
    {
        return $this->belongsTo(User::class, 'last_editor_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function attachment()
    {
        return $this->HasOne(NoteAttachment::class);
    }
}
