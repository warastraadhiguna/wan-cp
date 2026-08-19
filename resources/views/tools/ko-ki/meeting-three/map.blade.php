@extends('tools.ko-ki.layout')

@section('title', 'Peta Rescue Mission')

@section('content')
    <div class="rescue-map mx-auto" data-rescue-map data-state='@json($rescueState)' data-mission-url="{{ route('tools.ko-ki.meeting-three.mission', 1) }}">
        <header class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <p class="koki-eyebrow mb-1">Pertemuan 3 · Taman Kota</p>
                <h1 class="display-5 koki-title mb-1">Peta Rescue Mission</h1>
                <p class="koki-muted mb-0">Halo, {{ $progress->student_name }} · Kelas {{ $progress->grade }}. Selesaikan misi lalu tunggu izin admin.</p>
            </div>
            <div class="rescue-map-stars">⭐ <strong data-total-stars>0</strong> / 18</div>
        </header>
        @if (session('status')) <div class="alert alert-info rounded-4 fw-bold">{{ session('status') }}</div> @endif
        <section class="koki-card p-3 p-md-4">
            <div class="rescue-map-path" data-rescue-levels></div>
        </section>
        <div class="rescue-star-guide mt-3">
            <span>⭐ Misi selesai</span><span>⭐⭐ Prediksi benar</span><span>⭐⭐⭐ Program efisien</span>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ko-ki/rescue-mission.css') }}?v={{ filemtime(public_path('css/ko-ki/rescue-mission.css')) }}">
@endpush
@push('scripts')
    <script type="module" src="{{ asset('js/ko-ki/rescue-map.js') }}?v={{ filemtime(public_path('js/ko-ki/rescue-map.js')) }}"></script>
@endpush
