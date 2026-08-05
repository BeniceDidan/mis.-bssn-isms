<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atur Ulang Kata Sandi &mdash; BSSN ISMS</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="relative flex h-full items-center justify-center overflow-hidden bg-gradient-to-br from-sky-100 via-white to-indigo-100 font-sans antialiased">
    <div class="pointer-events-none fixed inset-0 -z-20 overflow-hidden">
        <div class="blob-float absolute -left-24 -top-24 h-96 w-96 rounded-full bg-sky-300/50 blur-3xl"></div>
        <div class="blob-float-delay absolute -right-20 top-1/4 h-80 w-80 rounded-full bg-purple-300/40 blur-3xl"></div>
        <div class="blob-float-slow absolute bottom-0 left-1/3 h-96 w-96 rounded-full bg-teal-200/50 blur-3xl"></div>
    </div>

    <livewire:auth.reset-password :token="$token" :email="request()->query('email', '')" />

    @livewireScripts
</body>
</html>
