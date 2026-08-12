@extends('tools.ko-ki.layout')

@section('title', 'Level '.$level.' — '.$levelDefinition['name'])

@section('content')
    @php
        $hasConveyor = in_array('conveyor', $levelDefinition['systems'], true);
        $visibleStations = collect($factory['stations'])->reject(
            fn (array $station): bool => ($hasConveyor && $station['type'] === 'ingredient')
                || ($station['type'] === 'fridge' && ! in_array('double_order', $levelDefinition['systems'], true)),
        );
        $stationMap = $visibleStations->keyBy(fn (array $station): string => $station['x'].'-'.$station['y']);
        $runtime = $factoryState['level_progress'][$level]['runtime'] ?? null;
        $needsTrainingReplay = $level === 1 && in_array(1, $factoryState['completed_levels'], true) && ! $factoryState['training_replay_seen'];
    @endphp
    <div
        class="factory-game {{ $hasConveyor ? 'has-conveyor' : '' }}"
        data-factory-rush
        data-level="{{ $level }}"
        data-save-url="{{ route('tools.ko-ki.meeting-two.update') }}"
        data-entry-url="{{ route('tools.ko-ki.meeting-two.welcome') }}"
        data-map-url="{{ route('tools.ko-ki.meeting-two.map') }}"
        data-finale-url="{{ route('tools.ko-ki.meeting-two.completed') }}"
        data-state='@json($factoryState)'
        data-config='@json($factory)'
        tabindex="-1"
    >
        <header class="factory-hud">
            <a class="factory-back" href="{{ route('tools.ko-ki.meeting-two.map') }}" aria-label="Kembali ke daftar level"><i class="bi bi-grid-fill"></i></a>
            <div class="factory-title-block">
                <span class="factory-live-dot" aria-hidden="true"></span>
                <div><small>LEVEL {{ $level }}</small><strong>{{ $levelDefinition['name'] }}</strong></div>
            </div>
            <div class="factory-orders" data-orders aria-label="Order aktif"></div>
            @if ($levelDefinition['timer'])
                <div class="factory-timer" data-timer><small>WAKTU</small><strong>--:--</strong></div>
            @endif
            <div class="factory-score"><small>POIN</small><strong data-score>0</strong></div>
            <div class="factory-combo"><small>COMBO</small><strong data-combo>×0</strong></div>
            <button class="factory-pause-button" type="button" data-pause aria-label="Jeda permainan"><i class="bi bi-pause-fill"></i><span>Jeda</span></button>
        </header>

        <div class="factory-target-bar {{ in_array('double_order', $levelDefinition['systems'], true) ? 'is-double-target' : '' }}">
            @if (in_array('double_order', $levelDefinition['systems'], true))
                <div class="factory-target-condition">
                    <span>SCORE <strong data-score-progress>0 / {{ $levelDefinition['target_score'] }}</strong></span>
                    <div class="progress"><div class="progress-bar" data-score-target-progress style="width: 0%"></div></div>
                </div>
                <div class="factory-target-condition">
                    <span>DOUBLE DELIVERY <strong><b data-batches-done>0</b> / {{ $levelDefinition['minimum_batches'] }}</strong></span>
                    <div class="progress"><div class="progress-bar" data-batch-target-progress style="width: 0%"></div></div>
                </div>
            @else
                <span>Order selesai <strong data-orders-done>0</strong> / {{ $levelDefinition['target'] }}</span>
                <div class="progress"><div class="progress-bar" data-target-progress style="width: 0%"></div></div>
            @endif
            @if ($progress->grade === 5)<span class="factory-grade-bonus">Bonus kelas 5: jaga combo!</span>@endif
        </div>

        <div class="factory-layout">
            <section class="factory-arena-wrap" aria-label="Arena pabrik jus">
                <div class="factory-arena" style="--factory-cols: {{ $factory['grid']['width'] }}; --factory-rows: {{ $factory['grid']['height'] }}" data-arena>
                    @for ($y = 0; $y < $factory['grid']['height']; $y++)
                        @for ($x = 0; $x < $factory['grid']['width']; $x++)
                            @php($station = $stationMap->get($x.'-'.$y))
                            @php($isConveyor = $hasConveyor && $y === 0 && $x >= 2 && $x <= 7)
                            <div class="factory-tile {{ $station ? 'has-station station-'.$station['type'] : '' }} {{ $isConveyor ? 'is-conveyor' : '' }}" data-tile data-x="{{ $x }}" data-y="{{ $y }}">
                                @if ($station)
                                    <div class="factory-station" data-station="{{ $station['id'] }}">
                                        <span class="factory-station-icon" aria-hidden="true">{{ $station['icon'] }}</span>
                                        <small>{{ $station['label'] }}</small>
                                        @if ($station['type'] === 'blender')<div class="factory-blender-fill" data-blender-fill></div>@endif
                                        @if ($station['type'] === 'fridge')
                                            <div class="factory-fridge-door" data-fridge-door><span data-fridge-mini-slot="0">＋</span><span data-fridge-mini-slot="1">＋</span></div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endfor
                    @endfor
                    @if ($hasConveyor)
                        <div class="factory-conveyor-label">CONVEYOR ▶</div>
                        <div class="factory-conveyor-item" data-conveyor-item hidden></div>
                    @endif
                    @if (in_array('obstacle', $levelDefinition['systems'], true))
                        <div class="factory-cleaner" data-cleaner aria-label="Robot pembersih">🧹<small>BOT</small></div>
                    @endif
                    @if (in_array('powerups', $levelDefinition['systems'], true))
                        <div class="factory-powerup" data-powerup hidden></div>
                    @endif
                    <div class="factory-player" data-player aria-label="Ko-Ki Bot">
                        <span class="factory-held" data-held-icons hidden></span>
                        <span class="factory-bot">🤖</span>
                        <span class="factory-direction" data-direction aria-hidden="true">▼</span>
                    </div>
                    <div class="factory-action-hint" data-action-hint hidden>SPACE</div>
                </div>
                <div class="factory-message" data-message role="status" aria-live="polite">Tekan Mulai Level untuk memulai shift.</div>
                @if (in_array('double_order', $levelDefinition['systems'], true))
                    <div class="factory-double-ready-banner" aria-live="polite">✨ DOUBLE ORDER READY! ✨</div>
                @endif
            </section>

            <aside class="factory-sidebar">
                <section class="factory-panel factory-hand-panel">
                    <div><small>TAS KO-KI</small><strong data-inventory-label>Kosong</strong></div>
                    <div class="factory-inventory" data-inventory><span>👐</span></div>
                </section>
                <section class="factory-panel">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <strong>Isi Blender</strong><span class="badge rounded-pill text-bg-light border" data-blender-status>Kosong</span>
                    </div>
                    <div class="factory-blender-slots" data-blender-slots><span>＋</span><span>＋</span><span>＋</span></div>
                </section>
                @if (in_array('double_order', $levelDefinition['systems'], true))
                    <section class="factory-panel factory-fridge-panel">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <strong>🧊 Kulkas</strong><span class="badge rounded-pill text-bg-light border">2 slot</span>
                        </div>
                        <div class="factory-fridge-slots" data-fridge-slots><span>＋</span><span>＋</span></div>
                        <small>Simpan jus pertama di sini.</small>
                    </section>
                @endif
                <section class="factory-panel factory-controls-panel">
                    <strong class="d-block mb-2">Kontrol</strong>
                    <div class="factory-key-guide">
                        <span><kbd>↑↓←→</kbd> / <kbd>WASD</kbd><small>Bergerak</small></span>
                        <span><kbd>SPACE</kbd><small>Ambil / gunakan</small></span>
                    </div>
                    <div class="factory-dpad" aria-label="Kontrol layar sentuh">
                        <button type="button" data-move="north" aria-label="Maju ke atas">▲</button>
                        <button type="button" data-move="west" aria-label="Bergerak ke kiri">◀</button>
                        <button class="is-action" type="button" data-interact>SPACE</button>
                        <button type="button" data-move="east" aria-label="Bergerak ke kanan">▶</button>
                        <button type="button" data-move="south" aria-label="Bergerak ke bawah">▼</button>
                    </div>
                </section>
                <section class="factory-panel factory-recipe-panel">
                    <strong class="d-block mb-2">Resep</strong>
                    <div>🍎 + 💧 = 🧃</div><div>🍌 + 💧 = 🥤</div><div>🍎 + 🍌 + 💧 = 🍹</div>
                </section>
                @if ($level === 1)
                    <section class="factory-panel factory-tutorial-strip" aria-label="Tutorial visual">
                        <strong>Tutorial</strong>
                        <div><span>1</span><b>⌨️ Gerak</b><i>→</i><span>2</span><b>👐 Ambil</b><i>→</i><span>3</span><b>⚙️ Blender</b><i>→</i><span>4</span><b>📦 Antar</b></div>
                    </section>
                @endif
                <div class="factory-effects" data-effects></div>
                <p class="factory-save-state mb-0" data-save-state>Progress disimpan saat memakai mesin, kulkas, atau delivery.</p>
                <p class="alert alert-danger rounded-4 mb-0" data-factory-error hidden></p>
            </aside>
        </div>

        <div class="factory-overlay" data-start-overlay {{ ($runtime['active'] ?? false) || $needsTrainingReplay ? 'hidden' : '' }}>
            <div class="factory-overlay-card">
                <div class="display-3 mb-2" aria-hidden="true">{{ $levelDefinition['icon'] }}</div>
                <p class="koki-eyebrow mb-1">Level {{ $level }}</p>
                <h1 class="h2 fw-bold">{{ $levelDefinition['name'] }}</h1>
                <p>{{ $levelDefinition['description'] }}</p>
                @if (in_array('double_order', $levelDefinition['systems'], true))
                    <p class="badge rounded-pill text-bg-primary fs-6 px-3 py-2">Target {{ $levelDefinition['target_score'] }} poin + {{ $levelDefinition['minimum_batches'] }} Double Delivery</p>
                @else
                    <p class="badge rounded-pill text-bg-primary fs-6 px-3 py-2">Target {{ $levelDefinition['target'] }} order</p>
                @endif
                <button class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold d-block mx-auto" type="button" data-start-level>
                    {{ ($factoryState['level_progress'][$level]['completed'] ?? false) ? 'Mainkan Lagi' : 'Mulai Level' }}
                </button>
            </div>
        </div>
        <div class="factory-overlay" data-training-replay {{ $needsTrainingReplay ? '' : 'hidden' }}>
            <div class="factory-overlay-card factory-learning-card">
                <p class="koki-eyebrow mb-1">Replay Training</p>
                <h2 class="h2 fw-bold mb-3">Jus pertamamu berhasil!</h2>
                <div class="factory-replay-flow mb-3">
                    <div><span>🍎 + 💧</span><small>Apel + Air</small></div><b>↓</b>
                    <div><span>⚙️</span><small>Blender</small></div><b>↓</b>
                    <div><span>🧃</span><small>Jus Apel</small></div>
                </div>
                <div class="factory-ipo-labels mb-3">
                    <div><small>INPUT</small><strong>Apel + Air</strong></div>
                    <div><small>PROSES</small><strong>Blender mencampur bahan</strong></div>
                    <div><small>OUTPUT</small><strong>Jus Apel</strong></div>
                </div>
                <p class="factory-learning-copy">Yang masuk disebut <b>Input</b>. Yang dikerjakan disebut <b>Proses</b>. Hasilnya disebut <b>Output</b>.</p>
                <button class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold" type="button" data-finish-training-replay>Selesai Training</button>
            </div>
        </div>
        <div class="factory-overlay" data-pause-overlay hidden>
            <div class="factory-overlay-card">
                <div class="display-3" aria-hidden="true">⏸️</div>
                <h2 class="h2 fw-bold">Permainan Dijeda</h2>
                <p>Timer juga berhenti. Lanjutkan saat kamu siap.</p>
                <div class="d-grid gap-2 mx-auto factory-pause-actions">
                    <button class="btn btn-primary btn-lg rounded-pill fw-bold" type="button" data-resume>▶ Lanjutkan</button>
                    <button class="btn btn-outline-danger btn-lg rounded-pill fw-bold" type="button" data-restart>↻ Restart Level</button>
                    <a class="btn btn-outline-secondary rounded-pill fw-bold" href="{{ route('tools.ko-ki.meeting-two.map') }}">Pilih Level</a>
                </div>
            </div>
        </div>
        <div class="factory-overlay" data-result-overlay hidden>
            <div class="factory-overlay-card">
                <div class="display-2 mb-2" data-result-icon>🎉</div>
                <h2 class="h2 fw-bold" data-result-title>Level Selesai!</h2>
                <p data-result-message>Target order berhasil dicapai.</p>
                <div class="factory-result-stats"><span><small>POIN</small><strong data-result-score>0</strong></span><span><small>COMBO</small><strong data-result-combo>×0</strong></span></div>
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-3">
                    <a class="btn btn-primary rounded-pill px-4 fw-bold" href="{{ route('tools.ko-ki.meeting-two.map') }}">Pilih Level</a>
                    <button class="btn btn-outline-primary rounded-pill px-4 fw-bold" type="button" data-replay-level>Mainkan Lagi</button>
                </div>
            </div>
        </div>
        <div class="factory-celebration" data-celebration hidden aria-live="assertive"><span>+100</span><strong>ORDER BERHASIL!</strong></div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ko-ki/factory-rush.css') }}?v={{ filemtime(public_path('css/ko-ki/factory-rush.css')) }}">
@endpush

@push('scripts')
    <script type="module" src="{{ asset('js/ko-ki/factory-rush.js') }}?v={{ filemtime(public_path('js/ko-ki/factory-rush.js')) }}"></script>
@endpush
