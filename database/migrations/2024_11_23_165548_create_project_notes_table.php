<?php

use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_notes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text('content');
            $table->boolean('is_pinned')->default(false);
            $table->foreignIdFor(Project::class);
            $table->foreignIdFor(User::class, 'author_id');
            $table->foreignIdFor(User::class, 'last_editor_id')->nullable();
        });
        Schema::create('note_attachments', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->longText('path');
            $table->longText('name');
            $table->foreignIdFor(ProjectNote::class)->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_notes');
        Schema::dropIfExists('note_attachments');
    }
};
