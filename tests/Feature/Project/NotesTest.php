<?php

namespace Tests\Feature\Project;

use App\Enums\ProjectUserRoles;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Volt\Volt;
use Tests\TestCase;

class NotesTest extends TestCase
{
    use RefreshDatabase;

    private $markdown = <<<MD
        # Welcome to Markdown

        Markdown is a lightweight markup language for formatting text. Here's a quick overview of some basic syntax:

        ## Formatting Text
        - **Bold**: `**text**` or `__text__`
        - *Italic*: `*text*` or `_text_`
        - ~~Strikethrough~~: `~~text~~`

        ## Lists
        1. Ordered lists are easy.
        2. Just use numbers.
        3. Like this!

        - Unordered lists use `-`, `+`, or `*`.

        ## Links and Images
        - [Link to Google](https://google.com)
        - ![Placeholder Image](https://via.placeholder.com/150)

        ## Code
        Inline code looks like this: `console.log('Hello, Markdown!');`

        Block code:
        ```javascript
        function greet() {
            console.log("Hello, Markdown!");
        }
        ```
        MD;

    public function test_user_can_create_note_without_attachments()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->users()->attach($user, ['role' => ProjectUserRoles::OWNER]);

        $this->actingAs($user);
        $component = Volt::test('project.note-modal',
            ['project' => $project])
            ->assertSee('Create Note')
            ->set('text', 'Test Note that is more than 10 characters')
            ->call('createOrUpdateNote')
            ->assertHasNoErrors()
            ->assertDispatched('refresh-notes');

        $this->assertDatabaseHas('project_notes', [
            'content' => 'Test Note that is more than 10 characters',
        ]);
    }

    public function test_user_can_create_note_with_a_valid_attachment()
    {
        $test_valid_attachment = function ($fileToTest) {
            $user = User::factory()->create();
            $project = Project::factory()->create();
            $project->users()->attach($user, ['role' => ProjectUserRoles::OWNER]);
            $uploadedFile = UploadedFile::fake()->create('document.pdf', 100);

            $this->actingAs($user);
            $component = Volt::test('project.note-modal',
                ['project' => $project])
                ->assertSee('Create Note')
                ->set('text', $this->markdown)
                ->set('attachment', $uploadedFile)
                ->call('createOrUpdateNote')
                ->assertHasNoErrors();

            $this->assertDatabaseHas('project_notes', [
                'content' => $this->markdown,
            ]);
            $this->assertDatabaseHas('note_attachments', ['name' => $uploadedFile->getClientOriginalName()]);
        };

        $filesToTest = [
            UploadedFile::fake()->create('document.doc', 10240, 'application/msword'),
            UploadedFile::fake()->create('document.pdf', 10240, 'application/pdf'),
            UploadedFile::fake()->create('document.docx', 10240, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ];
        foreach ($filesToTest as $file) {
            try {
                $test_valid_attachment($file);
            } catch (\Exception $e) {
                echo 'Error with file: ' . $file->getClientOriginalName() . "\n";
                throw $e;
            }
        }
    }

    public function test_user_cant_create_note_with_an_unvalid_attachment()
    {
        $test_invalid_attachment = function ($fileToTest) {
            $user = User::factory()->create();
            $project = Project::factory()->create();
            $project->users()->attach($user, ['role' => ProjectUserRoles::OWNER]);

            $this->actingAs($user);
            $component = Volt::test('project.note-modal',
                ['project' => $project])
                ->assertSee('Create Note')
                ->set('text', $this->markdown)
                ->set('attachment', $fileToTest)
                ->call('createOrUpdateNote')
                ->assertHasErrors('attachment');

            $this->assertDatabaseMissing('project_notes', [
                'content' => $this->markdown,
            ]);
        };

        $filesToTest = [
            UploadedFile::fake()->image('image.jpeg')->size(10240),
            UploadedFile::fake()->image('image.png')->size(1800),
            UploadedFile::fake()->create('document.doc', 10241, 'application/msword'),
            UploadedFile::fake()->create('document.pdf', 10241, 'application/pdf'),
            UploadedFile::fake()->create('document.docx', 10241, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ];
        foreach ($filesToTest as $file) {
            try {
                $test_invalid_attachment($file);
            } catch (\Exception $e) {
                echo 'Error with file: ' . $file->getClientOriginalName() . "\n";
                throw $e;
            }
        }
    }
}
