@php
$appName = config('app.name');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    <title>{{$appName}}</title>

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="text-secondary">
    <header class="navbar bg-base-300 md:sticky top-0 z-10">
        <div class="avatar flex-1">
            <a href="{{ route('root') }}">
                <x-application-logo class="w-8 rounded-full" />
            </a>
        </div>

        <!-- Desktop Menu -->
        <div class="hidden md:flex menu menu-horizontal">
            <li> <a href="#why">Why {{ $appName }}?</a> </li>
            <li> <a href="{{ route('register') }}">Register</a> </li>
            <li> <a href="{{ route('login') }}">Login</a> </li>
        </div>
        <!-- Mobile Menu -->
        <div class="dropdown dropdown-end md:hidden">
            <div tabindex="0" role="button" class="btn btn-ghost btn-circle">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 rotate-180" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                </svg>
            </div>
            <ul tabindex="0"
                class="menu menu-lg md:menu-md dropdown-content bg-base-300 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                <li> <a href="#why">Why {{ $appName }}?</a> </li>
                <li> <a href="{{ route('register') }}">Register</a> </li>
                <li> <a href="{{ route('login') }}">Login</a> </li>
            </ul>
        </div>
        <x-theme-toggle darkTheme="synthwave" class="pr-4"/>
    </header>
    <main class="container mx-auto scroll-smooth">
        <section class="hero min-h-screen">
            <div class="hero-content text-center">
                <div class="space-y-4 text-primary prose-lg lg:prose-2xl prose-h1:font-bold">
                    <span class="!text-6xl">💃</span>
                    <h1>{{$appName}}</h1>
                    <p class="mb-8 font-normal text-secondary">Orchestrate your productivity with grace</p>
                    <a href="{{ route('register') }}" class="btn btn-primary">Start
                        Your Journey</a>
                    <p class="mt-6 text-secondary font-normal lg:prose-xl">
                        Already have an account? <a href="{{ route('login') }}" class="link link-primary">Log
                            in</a>
                    </p>
                </div>
            </div>
        </section>

        <section class="py-8  mx-4 lg:mx-0" id="why">
            <h2 class="text-3xl md:text-4xl font-bold text-primary mb-8 md:mb-12 text-center">Why Choose
                {{ $appName }}?
            </h2>
            <div class="grid md:grid-cols-3 gap-4 md:gap-8">
                <div class="card bg-base-200 p-4">
                    <figure>
                        <x-icon name="s-rocket-launch" class="h-12 md:h-24 avatar text-primary" />
                    </figure>
                    <div class="card-body">
                        <h3 class="card-title text-primary mx-auto">Unlimited Potential</h3>
                        <p><strong>No artificial limits</strong> on projects or tags. Your
                            creativity
                            knows no bounds, and neither do we.</p>
                    </div>
                </div>
                <div class="card bg-base-200 p-4">
                    <figure>
                        <x-icon name="s-chat-bubble-left-right" class="h-12 md:h-24 avatar text-primary" />
                    </figure>
                    <div class="card-body">
                        <h3 class="card-title text-primary mx-auto">Seamless Collaboration</h3>
                        <p>Share tasks, ideas, and success with your team. Together, we achieve
                            more.
                        </p>
                    </div>
                </div>
                <div class="card bg-base-200 p-4">
                    <figure>
                        <x-icon name="o-rectangle-group" class="h-12 md:h-24 avatar text-primary" />
                    </figure>
                    <div class="card-body">
                        <h3 class="card-title text-primary mx-auto">Intuitive Interface</h3>
                        <p>A clean, user-friendly design that lets you focus on what matters most
                            -
                            your tasks.</p>

                    </div>
                </div>
            </div>
        </section>

        <section class="pb-8 mx-4 md:mx-0">
            <div class="bg-base-200 p-8 card prose xl:prose-xl text-center prose-h2:font-bold text-secondary mx-auto">
                <h2 class="text-primary">Ready to Tango? 🎶</h2>
                <p>Discover a productive environment for your team
                    and
                    yourself today</p>
                <div class="flex gap-4 justify-center">
                    <a href="{{ route('register') }}" class="btn btn-primary">Sign
                        Up For Free</a>
                    <a href="{{ route('login') }}" class="btn btn-neutral">Log
                        In</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer footer-center bg-base-300 opacity-50 text-base-content p-4">
        <aside>
            <p>&copy; {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
        </aside>
    </footer>

    @livewireScripts
</body>

</html>
