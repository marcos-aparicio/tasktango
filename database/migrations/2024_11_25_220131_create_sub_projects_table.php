<?php

use App\Models\Project;
use App\Models\SubProject;
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
        Schema::create('subprojects', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->foreignIdFor(User::class, 'creator_id')->nullable();
            $table->foreignIdFor(Project::class);
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignIdFor(SubProject::class)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_projects');
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['sub_project_id']);
            $table->dropColumn('sub_project_id');
        });
    }
};
