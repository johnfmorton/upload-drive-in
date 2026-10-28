{{-- Recipient-facing share pages. Styles only: these pages need no JavaScript. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ isset($title) ? $title . ' · ' : '' }}{{ config('app.name', 'Upload Drive-In') }}</title>
    @include('components.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|instrument-serif:400&display=swap" rel="stylesheet" />
    @vite('resources/css/app.css')
</head>
<body class="font-sans text-warm-900 antialiased">
    <div class="min-h-dvh flex flex-col bg-cream-50">
        <header class="px-6 py-5">
            <x-application-logo class="h-8 w-auto text-warm-900" />
        </header>
        <main class="flex-1 flex justify-center px-4 sm:px-6 pb-12">
            <div class="w-full max-w-2xl">
                {{ $slot }}
            </div>
        </main>
        @include('components.footer')
    </div>
</body>
</html>
