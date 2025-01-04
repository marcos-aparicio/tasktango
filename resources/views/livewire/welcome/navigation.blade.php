<nav class="-mx-3 flex flex-1 justify-end">
    @auth
        <a href="{{ url('/index') }}" class="link link-primary">
            Index
        </a>
    @else
        <a href="{{ route('login') }}" class="link link-primary">
            Log in
        </a>

        @if (Route::has('register'))
            <a href="{{ route('register') }}"
                class="link link-primary">
                Register
            </a>
        @endif
    @endauth
</nav>
