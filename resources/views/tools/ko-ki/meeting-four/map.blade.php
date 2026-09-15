@extends('tools.ko-ki.layout')

@section('title', 'Papan Kasus Bug Detective')

@section('content')
    <div class="detective-shell mx-auto" data-detective-map data-progress-id="{{ $progress->getKey() }}" data-state='@json($detectiveState)' data-sync-url="{{ route('tools.ko-ki.meeting-four.sync') }}" data-notebook-url="{{ route('tools.ko-ki.meeting-four.notebook') }}" data-case-url="{{ route('tools.ko-ki.meeting-four.case', 1) }}">
        <header class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><p class="koki-eyebrow mb-1">Bug Detective Agency</p><h1 class="display-5 koki-title mb-1">Papan Kasus</h1><p class="koki-muted mb-0">Detektif {{ $progress->student_name }} · Kelas {{ $progress->grade }}. Selesaikan case lalu tunggu izin admin.</p><strong class="detective-rank" data-detective-rank>{{ $detectiveState['rank'] }}</strong></div><div class="d-flex flex-wrap align-items-center gap-2"><span class="detective-save-status" data-save-status>✓ Tersimpan</span><div class="detective-collection-count">⭐ <strong data-total-stars>{{ $detectiveState['total_stars'] }}/48</strong></div><div class="detective-collection-count">Bug Collection <strong data-collection-count>{{ $detectiveState['collection_count'] }}/15</strong></div></div></header>
        <div class="alert alert-success rounded-4 fw-bold fs-5" data-final-notebook @if (! $detectiveState['final_solved']) hidden @endif>🏆 Semua kasus terpecahkan! <a href="{{ route('tools.ko-ki.meeting-four.notebook') }}" class="alert-link">Buka Detective Notebook</a>.</div>
        <section class="koki-card p-3 p-md-4"><div class="detective-case-list" data-case-list></div></section>
        <section class="koki-card p-3 p-md-4 mt-4"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h4 fw-bold mb-0">🐞 Bug Collection</h2><strong>{{ $detectiveState['collection_count'] }} / 15</strong></div><div class="detective-collection" data-collection></div></section>
    </div>
@endsection

@push('styles')<link rel="stylesheet" href="{{ asset('css/ko-ki/detective.css') }}?v={{ filemtime(public_path('css/ko-ki/detective.css')) }}">@endpush
@push('scripts')<script type="module" src="{{ asset('js/ko-ki/detective-map.js') }}?v={{ filemtime(public_path('js/ko-ki/detective-map.js')) }}"></script>@endpush
