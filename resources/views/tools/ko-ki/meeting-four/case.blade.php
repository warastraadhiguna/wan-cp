@extends('tools.ko-ki.layout')

@section('title', 'Case '.$caseNumber.' — Bug Detective')

@section('content')
    <div class="detective-game" data-detective-game data-case="{{ $caseNumber }}" data-progress-id="{{ $progress->getKey() }}" data-state='@json($detectiveState)' data-sync-url="{{ route('tools.ko-ki.meeting-four.sync') }}" data-map-url="{{ route('tools.ko-ki.meeting-four.map') }}" data-notebook-url="{{ route('tools.ko-ki.meeting-four.notebook') }}">
        <header class="detective-game-header"><a href="{{ route('tools.ko-ki.meeting-four.map') }}" class="btn btn-outline-secondary rounded-pill fw-bold"><i class="bi bi-grid-fill"></i> Papan Kasus</a><div><p class="koki-eyebrow mb-0" data-case-code>CASE</p><h1 class="h2 fw-bold mb-0" data-case-title>Investigasi Bug</h1></div><div class="d-flex align-items-center gap-2"><span class="detective-save-status" data-save-status>✓ Tersimpan</span><div class="detective-collection-count">Bug Collection <strong data-local-collection>{{ $detectiveState['collection_count'] }}/15</strong></div></div></header>

        <div class="detective-game-layout">
            <section class="detective-scene-panel">
                <div class="detective-brief"><span data-case-icon>🔎</span><div><strong data-case-subtitle></strong><small data-case-concept></small></div></div>
                <div class="detective-boss-bugs" data-boss-bugs hidden><span data-bug-status="1">🐞 BUG 1</span><span data-bug-status="2">🐞 BUG 2</span><span data-bug-status="3">🐞 BUG 3</span></div>
                <div class="detective-scene" data-scene></div>
                <div class="detective-status" data-status role="status" aria-live="polite">Tekan REPLAY untuk melihat program aneh ini bekerja.</div>
                <div class="detective-trace" data-trace hidden><strong>🔎 Jejak Aksi</strong><ol data-trace-list></ol></div>
            </section>

            <aside class="detective-workbench">
                <section class="detective-file"><div class="d-flex justify-content-between align-items-center"><strong>Timeline Command</strong><span class="badge rounded-pill text-bg-light border" data-step-counter>0 / 0</span></div><ol class="detective-timeline" data-timeline></ol></section>
                <div class="detective-toolbar" aria-label="Peralatan detektif">
                    <button type="button" data-action="replay">▶ <span>Replay</span></button>
                    <button type="button" data-action="step">⏭ <span>Step</span></button>
                    <button type="button" data-action="trace">👣 <span>Trace</span></button>
                    <button type="button" data-action="mark" disabled>📌 <span>Mark Suspect</span></button>
                    <button type="button" data-action="repair" disabled>🛠 <span>Repair</span></button>
                    <button type="button" data-action="test" disabled>🧪 <span>Test Fix</span></button>
                </div>
                <section class="detective-repair" data-repair-panel hidden><strong data-repair-title>Perbaiki program</strong><p class="small mb-2" data-repair-help></p><div class="d-grid gap-2" data-repair-controls></div></section>
                <button type="button" class="detective-hint-button" data-hint>💡 Minta Petunjuk</button>
                <div class="detective-hint" data-hint-text hidden></div>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill fw-bold" data-restart-case>↻ Restart Case</button>
            </aside>
        </div>

        <div class="rescue-result" data-case-result hidden><div class="detective-confetti" data-confetti></div><div class="rescue-result-card detective-solved-card"><div class="display-2">🕵️‍♀️✅</div><p class="koki-eyebrow mb-1" data-solved-label>CASE SOLVED!</p><h2 data-solved-title>Bug ditemukan</h2><div class="detective-result-stars" data-result-stars>⭐</div><div class="detective-rank-reward" data-rank-reward hidden></div><div class="detective-unlocked-card" data-unlocked-card><span>🐞</span><div><small>KARTU BARU</small><strong data-solved-card></strong></div></div><p data-solved-message>Bug ditemukan, diperbaiki, lalu program berhasil dicoba kembali.</p><div class="d-flex flex-wrap justify-content-center gap-2"><a data-next-case class="btn btn-primary koki-btn-primary rounded-pill fw-bold px-4">Kembali ke Papan Kasus</a><a href="{{ route('tools.ko-ki.meeting-four.map') }}" class="btn btn-outline-primary rounded-pill fw-bold px-4">Lihat Koleksi</a></div></div></div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ko-ki/rescue-mission.css') }}?v={{ filemtime(public_path('css/ko-ki/rescue-mission.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/ko-ki/detective.css') }}?v={{ filemtime(public_path('css/ko-ki/detective.css')) }}">
@endpush
@push('scripts')<script type="module" src="{{ asset('js/ko-ki/detective-game.js') }}?v={{ filemtime(public_path('js/ko-ki/detective-game.js')) }}"></script>@endpush
