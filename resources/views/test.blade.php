<x-main-layout>
    @php
        $user = auth()->user();
        $user->is_super_admin = true;
    @endphp
</x-main-layout>
