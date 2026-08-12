@extends('tools.ko-ki.layout')

@section('title', 'Factory Rush')

@section('content')
    <div class="factory-intro" data-adventure-intro data-save-url="{{ route('tools.ko-ki.meeting-two.update') }}" data-map-url="{{ route('tools.ko-ki.meeting-two.map') }}" data-entry-url="{{ route('tools.ko-ki.meeting-two.welcome') }}">
        <section class="koki-card p-4 p-md-5 text-center">
            <p class="koki-eyebrow mb-2">WAn Ko-Ki · Pertemuan 2</p>
            <h1 class="display-4 koki-title mb-3">Factory Rush</h1>
            <div class="factory-intro-scene" aria-hidden="true">
                <span>🍎</span><span>🍌</span><span class="factory-intro-bot">🤖</span><span>⚙️</span><span>📦</span>
            </div>
            <div class="koki-dialogue mx-auto my-4 factory-intro-dialogue">
                Halo, {{ $progress->student_name }}! Order jus sudah masuk. Bantu Ko-Ki menjalankan pabrik kecil ini.
            </div>
            <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
                <span class="badge rounded-pill text-bg-light border fs-6 px-3 py-2">Bergerak: Arrow / WASD</span>
                <span class="badge rounded-pill text-bg-light border fs-6 px-3 py-2">Gunakan: SPACE</span>
            </div>
            <button class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 py-3 fw-bold" type="button" data-enter-city>
                Mulai Shift <i class="bi bi-play-fill ms-1" aria-hidden="true"></i>
            </button>
            <p class="alert alert-danger rounded-4 mt-3 mb-0" data-adventure-error hidden aria-live="assertive"></p>
        </section>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ko-ki/factory-rush.css') }}?v={{ filemtime(public_path('css/ko-ki/factory-rush.css')) }}">
@endpush

@push('scripts')
    <script type="module" src="{{ asset('js/ko-ki/adventure.js') }}?v={{ filemtime(public_path('js/ko-ki/adventure.js')) }}"></script>
@endpush
