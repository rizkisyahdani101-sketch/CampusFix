<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <title>{{ $title ?? 'Beranda' }} · {{ config('campus.brand') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="cf-app antialiased">
        <x-portal-frame title="{{ $title ?? config('campus.brand') }}">{{ $slot }}</x-portal-frame>

        @livewireScripts
    </body>
</html>
