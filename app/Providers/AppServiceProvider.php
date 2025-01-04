<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\ProjectNote;
use App\Policies\TaskPolicy;
use App\View\Components\CustomChoices;
use App\View\Components\CustomDropdown;
use App\View\Components\CustomFile;
use App\View\Components\CustomMain;
use App\View\Components\CustomMenuSub;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // blade components
        Blade::component('custom-menu-sub', CustomMenuSub::class);
        Blade::component('custom-file', CustomFile::class);
        Blade::component('custom-choices', CustomChoices::class);
        Blade::component('custom-main', CustomMain::class);
        Blade::component('custom-dropdown', CustomDropdown::class);

        // enforce https in production
        if (App::environment('production')) {
            URL::forceScheme('https');
        }
        // define gates
        Gate::define('view-task', [TaskPolicy::class, 'view']);
        Gate::define('delete-task', [TaskPolicy::class, 'delete']);

        // route model binding(for volt components to work)
        Route::model('project', Project::class);
        Route::model('note', ProjectNote::class);
    }
}
