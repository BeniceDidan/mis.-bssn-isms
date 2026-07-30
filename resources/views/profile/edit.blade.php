@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <div>
        <h1 class="text-xl font-semibold text-gray-900">Profil Saya</h1>
        <p class="text-sm text-gray-500">Kelola informasi akun dan kata sandi Anda</p>
    </div>

    <div
        class="max-w-2xl space-y-6"
        x-data="{ toast: null }"
        x-on:toast.window="toast = $event.detail.message; setTimeout(() => toast = null, 3000)"
    >
        <livewire:profile.update-profile-information />
        <livewire:profile.update-password />

        <div
            x-show="toast"
            x-transition
            x-cloak
            class="fixed bottom-5 right-5 z-50 rounded-md bg-slate-900 px-4 py-2.5 text-sm text-white shadow-lg"
        >
            <span x-text="toast"></span>
        </div>
    </div>
@endsection
