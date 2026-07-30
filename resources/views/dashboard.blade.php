@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div>
        <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
        <p class="text-sm text-gray-500">Pilih modul untuk mulai bekerja</p>
    </div>

    <livewire:dashboard />
@endsection
