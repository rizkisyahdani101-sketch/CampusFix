@if (auth()->check())
    <x-portal-layout title="Pusat bantuan">
        @include('help.content')
    </x-portal-layout>
@else
    <x-auth-layout title="Pusat bantuan" description="Panduan singkat untuk melaporkan dan memantau perbaikan fasilitas kampus.">
        @include('help.content')
    </x-auth-layout>
@endif
