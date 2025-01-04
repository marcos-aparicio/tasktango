<?php

use App\Http\Controllers\Auth\ProviderController;
use App\Livewire\Segments\Calendar;
use App\Livewire\Segments\Inbox;
use App\Livewire\Segments\Label;
use App\Livewire\Segments\Next7;
use App\Livewire\Segments\ProjectIndex;
use App\Livewire\Segments\Today;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Oauth routes
Route::get('/auth/{provider}/redirect', [ProviderController::class, 'redirect'])->name('oauth.redirect');
Route::get('/auth/{provider}/callback', [ProviderController::class, 'callback'])->name('oauth.callback');

Route::get('/', function () {
    if (auth()->check() && auth()->user()->isSuperAdmin())
        return redirect()->route('admin');

    if (auth()->check())
        return redirect()->route('index');

    return view('welcome');
})->name('root');

Route::impersonate();

Route::prefix('project/{project}')
    ->middleware(['auth', 'verified', 'can:view,project'])
    ->name('project.')
    ->group(function () {
        Volt::route('/', ProjectIndex::class)
            ->name('show');
        Route::get('today', Today::class)
            ->name('today');
        Route::get('next-7-days', Next7::class)
            ->name('next-7-days');
        Volt::route('calendar', Calendar::class)
            ->name('calendar');
        Volt::route('labels', 'segments.labels')
            ->name('labels');

        Volt::route('members', 'project.pages.members')
            ->name('members');
        Volt::route('stats', 'project.pages.stats')
            ->name('stats');

        Volt::route('search', 'project.pages.search')
            ->name('search');

        Volt::route('notes', 'project.pages.notes')
            ->name('notes');
        Volt::route('note/{note}', 'project.pages.note')
            ->name('note');

        Route::get('/label/{label}', Label::class)
            ->name('label');
    });

Route::get('/admin', function () {
    return redirect()->route('admin.users');
})->middleware(['auth', 'verified', 'is-super-admin'])->name('admin');

Volt::route('admin/users', 'admin.users-table')
    ->middleware(['auth', 'verified', 'is-super-admin'])
    ->name('admin.users');

Volt::route('admin/projects', 'admin.projects-table')
    ->middleware(['auth', 'verified', 'is-super-admin'])
    ->name('admin.projects');

Route::get('/index', function () {
    if (auth()->check() && auth()->user()->isSuperAdmin())
        return redirect()->route('admin');

    if (auth()->check())
        return redirect()->route('inbox');

    return view('welcome');
})->name('index');

Route::middleware(['auth', 'verified', 'no-super-admins'])->group(function () {
    Volt::route('search', 'segments.search')->name('search');
    // functionality related
    Route::get('/inbox', Inbox::class)->name('inbox');
    Route::get('/today', Today::class)->name('today');
    Route::get('/next-7-days', Next7::class)->name('next-7-days');
    Volt::route('calendar', 'segments.calendar')->name('calendar');
    Volt::route('projects', 'segments.projects')->name('projects');
    Volt::route('labels', 'segments.labels')->name('labels');
    Route::get('/label/{label}', Label::class)
        ->middleware('can:view,label')
        ->name('label');
});

// auth related

Route::get('profile', function () {
    return auth()->user()->isSuperAdmin()
        ? view('profile-admin')
        : view('profile');
})
    ->middleware(['auth', 'verified'])
    ->name('profile');

Volt::route('picture', 'set-profile-picture')
    ->middleware(['auth'])
    ->name('picture');

if (config('app.env') !== 'production') {
    Route::get('/test', function () {
        return view('test');
    });
}

require __DIR__ . '/auth.php';
