@extends('tools.ko-ki.layout')

@section('title', 'Eksperimen Ko-Ki Bot')

@section('content')
    <div
        data-ko-ki-game
        data-grade="{{ $progress->grade }}"
        data-current-level="{{ $progress->current_level ?? 1 }}"
        data-programs='@json($progress->level_programs ?? [])'
        data-attempts='@json($progress->level_attempts ?? [])'
        data-predictions='@json($progress->predictions ?? [])'
        data-level-states='@json($progress->level_states ?? [])'
        data-completed-levels='@json($progress->completed_levels ?? [])'
        data-approved-levels='@json($approvedLevels)'
        data-save-url="{{ route('tools.ko-ki.progress.update') }}"
        data-level-status-url="{{ route('tools.ko-ki.levels.status') }}"
        data-laboratory-url="{{ route('tools.ko-ki.laboratory') }}"
    >
        <div class="koki-activity-header d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-3">
            <div>
                <p class="koki-eyebrow mb-2">Pertemuan 1 · Eksperimen</p>
                <h1 class="display-6 koki-title mb-2">Halo, {{ $progress->student_name }}!</h1>
                <p class="koki-muted mb-0">Berikan command dan perhatikan apa yang dilakukan Ko-Ki.</p>
            </div>
            <span class="badge rounded-pill text-bg-light border px-3 py-2">
                Jumlah percobaan: <span data-attempt-count>{{ $progress->attempt_count }}</span>
            </span>
        </div>

        <section class="koki-card koki-game-card p-3 p-md-4 mb-3">
            <div class="row g-3 align-items-start">
                <div class="col-lg-7 d-flex flex-column align-items-center">
                    <div class="koki-grid" data-grid aria-label="Grid pergerakan Ko-Ki Bot"></div>
                    <p class="alert alert-light border rounded-4 w-100 mt-2 mb-0 py-2 text-center fw-semibold" data-engine-status aria-live="polite">
                        Coba beri Ko-Ki satu perintah.
                    </p>
                </div>

                <div class="col-lg-5">
                    <div data-sandbox>
                        <p class="koki-eyebrow mb-2">Sandbox eksplorasi</p>
                        <h2 class="h3 fw-bold mb-3">Coba satu command</h2>

                        <div class="koki-dialogue mb-2">Aku bergerak karena kamu memberiku perintah.</div>
                        <div class="koki-dialogue mb-4">Putar hanya mengubah arahku.</div>

                        <div class="d-grid gap-2 mb-4">
                            <button class="btn btn-primary koki-btn-primary koki-command-button" type="button" data-sandbox-command="MOVE_FORWARD">
                                <i class="bi bi-arrow-up me-2" aria-hidden="true"></i> Maju
                            </button>
                            <div class="row g-2">
                                <div class="col-6">
                                    <button class="btn btn-outline-primary koki-command-button w-100" type="button" data-sandbox-command="TURN_LEFT">
                                        <i class="bi bi-arrow-counterclockwise me-1" aria-hidden="true"></i> Putar Kiri
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-primary koki-command-button w-100" type="button" data-sandbox-command="TURN_RIGHT">
                                        Putar Kanan <i class="bi bi-arrow-clockwise ms-1" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid d-sm-flex gap-2">
                            <button class="btn btn-outline-secondary btn-lg rounded-pill px-4" type="button" data-back-to-start>
                                Kembali ke Awal
                            </button>
                            <button class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-4" type="button" data-show-missions>
                                Lanjut ke Misi
                            </button>
                        </div>
                    </div>

                    <div data-missions hidden>
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <p class="koki-eyebrow mb-2" data-level-progress></p>
                                <h2 class="h3 fw-bold mb-0" data-level-title></h2>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary rounded-pill" type="button" data-show-sandbox>
                                Kembali ke Sandbox
                            </button>
                        </div>

                        <p class="koki-muted mb-2" data-level-instruction></p>
                        <p class="small fw-semibold mb-3">Percobaan misi ini: <span data-level-attempt>0</span></p>

                        <div class="border rounded-4 bg-light p-3 mb-3" data-prediction-panel hidden>
                            <p class="fw-bold mb-3" data-prediction-prompt></p>
                            <div class="d-grid gap-2" data-prediction-options></div>
                        </div>

                        <div class="alert alert-warning rounded-4" data-debug-panel hidden>
                            <p class="fw-bold mb-3" data-debug-prompt></p>
                            <button class="btn btn-warning rounded-pill fw-bold" type="button" data-observe-bug hidden>
                                Aku Sudah Mengamati
                            </button>
                        </div>

                        <p class="small koki-muted">Klik command untuk menambahkannya ke program.</p>

                        <div class="row g-2 mb-2">
                            <div class="col-12">
                                <button class="btn btn-primary koki-btn-primary koki-command-button w-100" type="button" data-add-command="MOVE_FORWARD">Maju</button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-primary koki-command-button w-100" type="button" data-add-command="TURN_LEFT">Putar Kiri</button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-primary koki-command-button w-100" type="button" data-add-command="TURN_RIGHT">Putar Kanan</button>
                            </div>
                        </div>

                        <div class="border rounded-4 bg-light p-2 mb-2">
                            <p class="text-center koki-muted mb-0" data-empty-program>Program masih kosong.</p>
                            <ol class="koki-program-list" data-program-list aria-label="Daftar command program"></ol>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-success rounded-pill fw-bold w-100 h-100" type="button" data-run>
                                    <i class="bi bi-play-fill me-1" aria-hidden="true"></i> Jalankan
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-primary koki-btn-primary rounded-pill fw-bold w-100 h-100" type="button" data-step>Langkah Satu per Satu</button>
                            </div>
                            <div class="col-6 col-sm-3">
                                <button class="btn btn-outline-danger btn-sm rounded-pill w-100 h-100" type="button" data-stop>Hentikan</button>
                            </div>
                            <div class="col-6 col-sm-3">
                                <button class="btn btn-outline-secondary btn-sm rounded-pill w-100 h-100" type="button" data-reset-robot>Reset Robot</button>
                            </div>
                            <div class="col-6 col-sm-3">
                                <button class="btn btn-outline-secondary btn-sm rounded-pill w-100 h-100" type="button" data-undo>Undo</button>
                            </div>
                            <div class="col-6 col-sm-3">
                                <button class="btn btn-outline-secondary btn-sm rounded-pill w-100 h-100" type="button" data-clear>Hapus Semua</button>
                            </div>
                        </div>

                        <div class="alert rounded-4 mt-3" data-mission-feedback hidden>
                            <div data-feedback-messages></div>
                            <div class="d-grid gap-2 mt-3">
                                <button class="btn btn-success rounded-pill fw-bold" type="button" data-next-level hidden>
                                    <i class="bi bi-hourglass-split me-1" aria-hidden="true"></i>
                                    <span data-next-level-label>Cek Izin Admin</span>
                                </button>
                                <button class="btn btn-outline-success rounded-pill fw-bold" type="button" data-bonus-retry hidden>
                                    Coba Bonus Opsional
                                </button>
                                <a class="btn btn-success rounded-pill fw-bold" href="{{ route('tools.ko-ki.laboratory') }}" data-open-laboratory hidden>
                                    Lanjut ke Laboratorium
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/ko-ki/activity.js') }}"></script>
@endpush
