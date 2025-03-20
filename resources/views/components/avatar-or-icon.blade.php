@props([ 'user' => null, 'forceToShowIcon' => false ])
@php
    $showAvatar = true;
    $avatar = '';
    if($user)
        $avatar = $user?->getProfilePictureUrl() ?? '';
    else {
        $showAvatar = false;
    }

    if($avatar == '' || $forceToShowIcon)
        $showAvatar = false;
@endphp
@if ($showAvatar && !$forceToShowIcon)
    <x-avatar :image="$avatar"
       {{ $attributes->except(['class', 'icon-class', 'avatar-class']) }}
       class="{{$attributes->get('avatar-class')}}" />
@else
    <x-icon name="o-user"
       {{ $attributes->except(['class', 'icon-class', 'avatar-class','name']) }}
        class="{{$attributes->get('icon-class')}}" />
@endif
