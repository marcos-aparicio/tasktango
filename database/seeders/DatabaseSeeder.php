<?php

namespace Database\Seeders;

use App\Enums\TaskPriorities;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (config('app.env') !== 'production') {
            User::factory()->create([
                'full_name' => 'Test User',
                'username' => 'test_user1',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
            ]);
            User::factory()->create([
                'full_name' => 'Test User',
                'username' => 'test_user2',
                'email' => 'test2@example.com',
                'password' => Hash::make('password'),
            ]);

            User::factory()->create([
                'full_name' => 'Test User',
                'username' => 'test_user3',
                'email' => 'test3@example.com',
                'password' => Hash::make('password'),
            ]);

            User::factory()->count(30)->create();
            $this->call(TaskSeeder::class);
            $this->call(ProjectSeeder::class);
        }

        if (User::where('is_super_admin', true)->doesntExist()) {
            User::factory()->create([
                'full_name' => 'Admin',
                'username' => env('SUPER_ADMIN_USERNAME'),
                'email' => env('SUPER_ADMIN_EMAIL'),
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD')),
                'is_super_admin' => true,
            ]);
        }

        if (config('app.env') === 'production') {
            $user1Email = 'marcos.aparicio593@gmail.com';
            $user2Email = 'marcos.aparicio1@outlook.com';
            $user3Email = 'marcos.aparicio-iliquin@mohawkcollege.ca';
        } else {
            $user1Email = 'test@example.com';
            $user2Email = 'test2@example.com';
            $user3Email = 'test3@example.com';
        }

        $this->demoSeeding($user1Email, $user2Email, $user3Email, 'Community Festival Planning');
        $this->demoSeeding($user2Email, $user1Email, $user3Email, 'Civic Festivity Management');
        $this->demoSeeding($user3Email, $user1Email, $user3Email, 'Festival Event Strategy');
    }

    /**
     * Generates a demo project with tasks, subprojects, and labels. Useful for demo purposes.
     *
     * @param string $ownerEmail
     * @param string $managerEmail
     * @param string $collaboratorEmail
     * @param string $projectName
     */
    public function demoSeeding($ownerEmail, $managerEmail, $collaboratorEmail, $projectName): void
    {
        $owner = User::where('email', $ownerEmail)->first();
        $manager = User::where('email', $managerEmail)->first();
        $collaborator = User::where('email', $collaboratorEmail)->first();

        if (!$owner || !$collaborator || !$manager) {
            logger('Users not found');
            return;
        }

        // project start
        $project1 = \App\Models\Project::factory()
            ->hasAttached($owner, ['role' => \App\Enums\ProjectUserRoles::OWNER])
            ->hasAttached($collaborator, ['role' => \App\Enums\ProjectUserRoles::COLLABORATOR])
            ->hasAttached($manager, ['role' => \App\Enums\ProjectUserRoles::MANAGER])
            ->create(
                [
                    'name' => $projectName,
                    'description' => 'Organizing an event with tasks for logistics, marketing, volunteer coordination, and more.'
                ]
            );

        $subprojects = $project1->subprojects()->createMany([
            ['name' => 'planning', 'creator_id' => $owner->id],
            ['name' => 'marketing', 'creator_id' => $collaborator->id],
            ['name' => 'logistics', 'creator_id' => $manager->id]
        ]);

        $coordinationLabel = $project1
            ->labels()
            ->create(['name' => 'coordination', 'user_id' => $owner->id]);
        $planningLabel = $project1->labels()->create(['name' => 'planning', 'user_id' => $owner->id]);
        $scheduleLabel = $project1->labels()->create(['name' => 'schedule', 'user_id' => $owner->id]);
        $volunteerLabel = $project1->labels()->create(['name' => 'volunteer', 'user_id' => $collaborator->id]);
        $vendorLabel = $project1->labels()->create(['name' => 'vendor', 'user_id' => $collaborator->id]);
        $foodLabel = $project1->labels()->create(['name' => 'food', 'user_id' => $collaborator->id]);
        $marketingLabel = $project1->labels()->create(['name' => 'marketing', 'user_id' => $collaborator->id]);
        $logisticsLabel = $project1->labels()->create(['name' => 'logistics', 'user_id' => $owner->id]);
        $supliesLabel = $project1->labels()->create(['name' => 'suplies', 'user_id' => $owner->id]);
        $safetyLabel = $project1->labels()->create(['name' => 'safety', 'user_id' => $manager->id]);
        $cleaningLabel = $project1->labels()->create(['name' => 'cleaning', 'user_id' => $manager->id]);
        $legalLabel = $project1->labels()->create(['name' => 'legal', 'user_id' => $collaborator->id]);
        $transportationLabel = $project1->labels()->create(['name' => 'transportation', 'user_id' => $owner->id]);
        $signageLabel = $project1->labels()->create(['name' => 'signage', 'user_id' => $collaborator->id]);
        $meetingLabel = $project1->labels()->create(['name' => 'meeting', 'user_id' => $manager->id]);

        // Tasks for Subproject 1 (UI/UX Design)
        $firstSubProjectTasks = [
            [
                'name' => 'Redesign Splash Screen',
                'priority' => TaskPriorities::P1,
                'labels' => [$coordinationLabel->id, $planningLabel->id]
            ],
            [
                'name' => 'Create design mockups for splash screen',
                'priority' => TaskPriorities::P2,
                'labels' => [$planningLabel->id, $scheduleLabel->id]
            ],
            [
                'name' => 'Review and adjust based on feedback',
                'priority' => TaskPriorities::P2,
                'labels' => [$scheduleLabel->id, $coordinationLabel->id]
            ],
            [
                'name' => 'Design User Onboarding Flow',
                'priority' => TaskPriorities::P1,
                'labels' => [$coordinationLabel->id, $planningLabel->id]
            ],
            [
                'name' => 'Design Event Flyers and Posters',
                'priority' => TaskPriorities::P2,
                'labels' => [$planningLabel->id, $vendorLabel->id]
            ],
            [
                'name' => 'Set Up Social Media Campaigns',
                'priority' => TaskPriorities::P2,
                'labels' => [$scheduleLabel->id, $volunteerLabel->id]
            ],
            [
                'name' => 'Create Event Website Page',
                'priority' => TaskPriorities::P2,
                'labels' => [$planningLabel->id, $coordinationLabel->id]
            ]
        ];

        // Tasks for Subproject 2 (Marketing and Outreach)
        $secondSubProjectTasks = [
            [
                'name' => 'Develop Event Promotion Plan',
                'priority' => TaskPriorities::P1,
                'labels' => [$vendorLabel->id, $marketingLabel->id]
            ],
            [
                'name' => 'Reach Out to Local Media for Coverage',
                'priority' => TaskPriorities::P2,
                'labels' => [$vendorLabel->id, $legalLabel->id]
            ],
            [
                'name' => 'Create Event Website Page',
                'priority' => TaskPriorities::P2,
                'labels' => [$marketingLabel->id, $vendorLabel->id]
            ]
        ];

        // Adding tasks to Subproject 1
        foreach ($firstSubProjectTasks as $taskData) {
            $labels = $taskData['labels'];
            unset($taskData['labels']);
            $task = $subprojects[0]->tasks()->create([
                'name' => $taskData['name'],
                'priority' => $taskData['priority'],
                'creator_user_id' => $owner->id,
                'project_id' => $project1->id
            ]);

            $task->labels()->sync($labels);
        }

        // Adding tasks to Subproject 2
        foreach ($secondSubProjectTasks as $taskData) {
            $labels = $taskData['labels'];
            unset($taskData['labels']);
            $task = $subprojects[1]->tasks()->create([
                'name' => $taskData['name'],
                'priority' => $taskData['priority'],
                'creator_user_id' => $collaborator->id,
                'project_id' => $project1->id
            ]);

            $task->labels()->sync($labels);
        }

        $thirdSubProjectTasks = [
            [
                'name' => 'Order supplies for festival booths',
                'priority' => TaskPriorities::P2,
                'labels' => [$logisticsLabel->id, $supliesLabel->id]
            ],
            [
                'name' => 'Set up Security and Safety Measures',
                'priority' => TaskPriorities::P1,
                'labels' => [$safetyLabel->id, $logisticsLabel->id]
            ],
            [
                'name' => 'Arrange for Event Waste` Management',
                'priority' => TaskPriorities::P2,
                'labels' => [$logisticsLabel->id, $cleaningLabel->id]
            ],
            [
                'name' => 'Confirm permits and licesing',
                'priority' => TaskPriorities::P1,
                'labels' => [$transportationLabel->id, $logisticsLabel->id]
            ],
            [
                'name' => 'Plan transportation and parking for attendees',
                'priority' => TaskPriorities::P2,
                'labels' => [$legalLabel->id, $logisticsLabel->id]
            ],
            [
                'name' => 'Set Up Event Signage and Directions',
                'priority' => TaskPriorities::P2,
                'labels' => [$logisticsLabel->id, $signageLabel->id]
            ]
        ];

        foreach ($thirdSubProjectTasks as $taskData) {
            $labels = $taskData['labels'];
            unset($taskData['labels']);
            $task = $subprojects[0]->tasks()->create([
                'name' => $taskData['name'],
                'priority' => $taskData['priority'],
                'creator_user_id' => $manager->id,
                'project_id' => $project1->id
            ]);

            $task->labels()->sync($labels);
        }

        // Tasks without a subproject (directly under the main project)
        $mainProjectTasks = [
            [
                'name' => 'Finalize event budget',
                'priority' => TaskPriorities::P1,
                'labels' => [$coordinationLabel->id, $vendorLabel->id]
            ],
            [
                'name' => 'Contact potential sponsors',
                'priority' => TaskPriorities::P1,
                'labels' => [$vendorLabel->id, $marketingLabel->id]
            ],
            [
                'name' => 'Organize volunteer training session',
                'priority' => TaskPriorities::P2,
                'labels' => [$volunteerLabel->id, $planningLabel->id]
            ],
            [
                'name' => 'Set up event registration platform',
                'priority' => TaskPriorities::P1,
                'labels' => [$planningLabel->id, $scheduleLabel->id]
            ],
            [
                'name' => 'Schedule project review meeting',
                'priority' => TaskPriorities::P2,
                'labels' => [$meetingLabel->id, $coordinationLabel->id]
            ]
        ];

        // Adding tasks to the main project (not assigned to any subproject)
        foreach ($mainProjectTasks as $taskData) {
            $labels = $taskData['labels'];
            unset($taskData['labels']);
            $task = $project1->tasks()->create([
                'name' => $taskData['name'],
                'priority' => $taskData['priority'],
                'creator_user_id' => $owner->id
            ]);

            $task->labels()->sync($labels);
        }
        // ADDING NOTES
        $project1->notes()->create([
            'content' => <<<MARKDOWN
                # IMPORTANT DATES
                The festival is scheduled for **August 15th, 2025**.

                The volunteer training session will be held on **August 10th, 2025**.

                Please make sure to mark these dates in your calendar and prepare accordingly.
                MARKDOWN,
            'is_pinned' => true,
            'author_id' => $owner->id
        ]);
        $project1->notes()->create([
            'content' => <<<MARKDOWN
                # VENUE INFORMATION
                The festival will be hosted at [Venue Name]. Ensure all permits are secured, vendors are set up in designated areas, and safety measures are in place. A detailed map of the venue will be shared with all team members ahead of the event.
                MARKDOWN,
            'author_id' => $manager->id
        ]);
    }
}
