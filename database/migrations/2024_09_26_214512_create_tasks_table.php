<?php

use App\Enums\TaskFrequencies;
use App\Enums\TaskPriorities;
use App\Enums\TaskStatuses;
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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(User::class, 'creator_user_id');
            $table
                ->foreignIdFor(User::class, 'assignee_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('order')->default(0);
            $table
                ->enum('status', array_column(TaskStatuses::cases(), 'value'));
            $table
                ->enum('frequency', array_column(TaskFrequencies::cases(), 'value'));
            $table
                ->enum('priority', array_column(TaskPriorities::cases(), 'value'));
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
