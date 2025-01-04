<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class NoteAttachment extends Model
{
    protected $fillable = ['path', 'name'];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            Storage::delete($attachment->path);
        });
    }

    public function note()
    {
        return $this->belongsTo(ProjectNote::class, 'project_note_id');
    }
}
