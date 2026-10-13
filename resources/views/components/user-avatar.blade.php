@props(['user' => null])

@php
    $avatarUser = $user ?? auth()->user();
    $avatarUrl = $avatarUser?->avatar_url;
    $name = $avatarUser?->name ?? 'Pengguna CampusFix';
    $initials = $avatarUser?->initials ?: mb_strtoupper(mb_substr($name, 0, 2));
@endphp

@if ($avatarUrl)
    <span {{ $attributes->class('cf-avatar') }}>
        <img src="{{ $avatarUrl }}" alt="Foto profil {{ $name }}">
    </span>
@else
    <span {{ $attributes->class('cf-avatar') }} aria-label="{{ $name }}">{{ $initials }}</span>
@endif
