@extends('tools.ko-ki.layout')

@section('title', 'Rescue Mission '.$mission)

@section('content')
    <div class="rescue-game" data-rescue-game data-mission="{{ $mission }}" data-state='@json($rescueState)' data-map-url="{{ route('tools.ko-ki.meeting-three.map') }}" data-save-url="{{ route('tools.ko-ki.meeting-three.progress') }}" data-evaluation-url="{{ route('tools.ko-ki.meeting-three.evaluation') }}">
        <header class="rescue-hud">
            <a href="{{ route('tools.ko-ki.meeting-three.map') }}" class="btn btn-outline-secondary rounded-pill fw-bold"><i class="bi bi-grid-fill"></i> Peta</a>
            <div><p class="koki-eyebrow mb-0" data-level-label>Misi</p><h1 class="h3 fw-bold mb-0" data-level-title>Rescue Mission</h1></div>
            <div class="rescue-battery"><small>BATAS COMMAND</small><strong><span data-command-count>0</span> / <span data-command-limit>0</span></strong></div>
            <div class="rescue-water"><small>AIR</small><strong data-water>💧 ×0</strong></div>
        </header>

        <div class="rescue-layout">
            <section class="rescue-world">
                <div class="rescue-brief">
                    <p class="mb-1 fw-bold" data-instruction></p><small data-tip></small>
                </div>
                <div class="rescue-grid" data-grid aria-label="Arena taman. Klik kotak untuk memprediksi posisi akhir."></div>
                <div class="rescue-world-status" data-status role="status" aria-live="polite">Susun seluruh program terlebih dahulu.</div>
            </section>

            <aside class="rescue-editor">
                <section class="rescue-panel">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <strong>1. Susun Command</strong><span class="badge rounded-pill text-bg-light border" data-program-size>0 command</span>
                    </div>
                    <div class="rescue-command-buttons">
                        <button type="button" data-add-command="MOVE_FORWARD">⬆️ <span>MAJU</span></button>
                        <button type="button" data-add-command="TURN_LEFT">↶ <span>PUTAR KIRI</span></button>
                        <button type="button" data-add-command="TURN_RIGHT">↷ <span>PUTAR KANAN</span></button>
                        <button type="button" data-add-command="TAKE_WATER">💧 <span>AMBIL AIR</span></button>
                        <button type="button" data-add-command="WATER">🌧️ <span>SIRAM</span></button>
                    </div>
                </section>

                <section class="rescue-panel rescue-program-panel">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <strong>Program Ko-Ki</strong>
                        <div><button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" data-undo>Hapus 1</button> <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-clear>Hapus Semua</button></div>
                    </div>
                    <ol class="rescue-program" data-program><li class="is-empty">Belum ada command.</li></ol>
                </section>

                <section class="rescue-panel rescue-prediction-panel">
                    <strong>2. Prediksi posisi akhir</strong>
                    <p class="small mb-2">Klik satu kotak pada taman.</p>
                    <div class="rescue-prediction-value" data-prediction>Belum dipilih</div>
                </section>

                <button type="button" class="rescue-run" data-run disabled>▶ RUN PROGRAM</button>
                <button type="button" class="btn btn-outline-primary rounded-pill fw-bold w-100" data-reset>↻ Kembali ke Editor</button>
            </aside>
        </div>

        <div class="rescue-result" data-result hidden>
            <div class="rescue-result-card">
                <span class="display-3" data-result-icon>🎉</span>
                <h2 class="fw-bold" data-result-title>Misi Selesai!</h2>
                <div class="rescue-earned-stars" data-result-stars>⭐</div>
                <p data-result-message></p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <button type="button" class="btn btn-outline-primary rounded-pill fw-bold px-4" data-result-retry>Coba Lagi</button>
                    <a class="btn btn-primary koki-btn-primary rounded-pill fw-bold px-4" data-result-next href="{{ route('tools.ko-ki.meeting-three.map') }}">Kembali ke Peta</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ko-ki/rescue-mission.css') }}?v={{ filemtime(public_path('css/ko-ki/rescue-mission.css')) }}">
@endpush
@push('scripts')
    <script type="module" src="{{ asset('js/ko-ki/rescue-mission.js') }}?v={{ filemtime(public_path('js/ko-ki/rescue-mission.js')) }}"></script>
@endpush
