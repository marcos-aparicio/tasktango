<?php

use App\Enums\TaskFrequencies;
use App\Enums\TaskPriorities;

return [
    'password_min_length' => 8,
    'password_max_length' => 72,
    'ask_profile_picture_after_account_creation' => true,
    'label_max_length' => 20,
    'tasks' => [
        'max_name_length' => 255,
        'max_description_length' => 1024,
        'priority_colors' => [
            TaskPriorities::P1->value => 'error',
            TaskPriorities::P2->value => 'warning',
            TaskPriorities::P3->value => 'success',
            TaskPriorities::P4->value => 'info',
            TaskPriorities::P5->value => 'gray-500',
        ],
        'frequencies' => [
            TaskFrequencies::DAILY->value => 'Daily',
            TaskFrequencies::NONE->value => 'None',
            TaskFrequencies::WEEKLY->value => 'Weekly',
            TaskFrequencies::BIWEEKLY->value => 'Biweekly',
            TaskFrequencies::MONTHLY->value => 'Monthly',
        ]
    ]
];
