@extends('layouts.app')

@section('title', 'Verifikasi')

@section('content')
    <div>
        <h1 class="text-xl font-semibold text-gray-900">Verifikasi</h1>
        <p class="text-sm text-gray-500">Tinjau data yang diajukan User dari seluruh modul sebelum disahkan — sesuai alur BPMN Pemeriksaan Data.</p>
    </div>

    <livewire:admin-verification-queue />
@endsection
