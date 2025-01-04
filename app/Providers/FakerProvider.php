<?php

namespace App\Providers;

use Faker\Generator;
use Illuminate\Support\ServiceProvider;

class FakerProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        if ($this->app->environment('production'))
            return;
        $faker = $this->app->make(Generator::class);
        $faker->addProvider(new \DavidBadura\FakerMarkdownGenerator\FakerProvider($faker));
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        fake()->addProvider(new \DavidBadura\FakerMarkdownGenerator\FakerProvider(fake()));
    }
}
