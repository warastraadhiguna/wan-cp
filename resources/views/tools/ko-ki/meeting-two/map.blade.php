@extends('tools.ko-ki.layout')

@section('title', 'Level Factory Rush')

@section('content')
    <div class="factory-level-map">
        <header class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <p class="koki-eyebrow mb-1">WAn Ko-Ki · Pertemuan 2</p>
                <h1 class="display-5 koki-title mb-1">Factory Rush</h1>
                <p class="koki-muted mb-0">Selesaikan lima shift pabrik. Semua level yang terbuka dapat dimainkan kembali.</p>
            </div>
            <div class="factory-map-summary">
                <span><small>LEVEL</small><strong>{{ count($factoryState['completed_levels']) }}/5</strong></span>
                <span><small>POIN TERBAIK</small><strong>{{ $factoryState['score'] }}</strong></span>
                <span><small>COMBO TERBAIK</small><strong>×{{ $factoryState['best_combo'] }}</strong></span>
            </div>
        </header>

        <section class="koki-card p-3 p-md-4">
            @if (session('status'))
                <div class="alert alert-info rounded-4">{{ session('status') }}</div>
            @endif
            <div class="factory-level-path">
                @foreach ($factory['levels'] as $level)
                    @php
                        $prerequisiteMet = in_array($level['number'], $factoryState['unlocked_levels'], true);
                        $isApproved = in_array($level['number'], $factoryState['approved_levels'], true);
                        $isUnlocked = $prerequisiteMet && $isApproved;
                        $levelProgress = $factoryState['level_progress'][$level['number']] ?? null;
                        $isCompleted = (bool) ($levelProgress['completed'] ?? false);
                    @endphp
                    <article class="factory-level-card {{ $isCompleted ? 'is-completed' : ($isUnlocked ? 'is-unlocked' : 'is-locked') }}">
                        <div class="factory-level-number">{{ $level['number'] }}</div>
                        <div class="factory-level-icon" aria-hidden="true">{{ $level['icon'] }}</div>
                        <div class="factory-level-copy">
                            <p class="koki-eyebrow mb-1">Level {{ $level['number'] }} · ±{{ $level['minutes'] }} menit</p>
                            <h2 class="h4 fw-bold mb-1">{{ $level['name'] }}</h2>
                            <p class="small koki-muted mb-2">{{ $level['description'] }}</p>
                            <div class="d-flex flex-wrap gap-1">
                                <span class="badge text-bg-light border">Target {{ $level['target'] }} order</span>
                                @if ($level['timer'])<span class="badge text-bg-warning">Timer {{ floor($level['timer'] / 60) }} menit</span>@endif
                                @if (in_array('conveyor', $level['systems'], true))<span class="badge text-bg-info">Conveyor</span>@endif
                                @if (in_array('double_order', $level['systems'], true))<span class="badge text-bg-primary">2 order</span>@endif
                                @if (in_array('powerups', $level['systems'], true))<span class="badge text-bg-success">Power-up</span>@endif
                            </div>
                        </div>
                        <div class="factory-level-result">
                            @if ($isCompleted)
                                <span class="factory-complete-mark">✓</span>
                                <small>Terbaik</small>
                                <strong>{{ $levelProgress['best_score'] }} poin</strong>
                                <span>Combo ×{{ $levelProgress['best_combo'] }}</span>
                            @elseif (! $isUnlocked)
                                <i class="bi bi-lock-fill fs-3" aria-hidden="true"></i>
                                <small>{{ $prerequisiteMet ? 'Menunggu izin admin' : 'Selesaikan level sebelumnya' }}</small>
                            @endif
                        </div>
                        @if ($isUnlocked)
                            <a class="btn {{ $isCompleted ? 'btn-outline-success' : 'btn-primary koki-btn-primary' }} rounded-pill fw-bold px-4" href="{{ route('tools.ko-ki.meeting-two.mission', 'level-'.$level['number']) }}">
                                {{ $isCompleted ? 'Mainkan Lagi' : (($levelProgress['runtime']['active'] ?? false) ? 'Lanjutkan' : 'Mainkan') }}
                            </a>
                        @elseif ($prerequisiteMet)
                            <a class="btn btn-outline-primary rounded-pill fw-bold px-4" href="{{ route('tools.ko-ki.meeting-two.map') }}">
                                <i class="bi bi-arrow-clockwise me-1"></i> Cek Izin Admin
                            </a>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        @if ($factoryState['factory_finished'])
            <section class="factory-finale-gate mt-3">
                <div class="fs-1" aria-hidden="true">🏁</div>
                <div class="flex-grow-1">
                    <p class="koki-eyebrow mb-1">Semua shift selesai</p>
                    <h2 class="h4 fw-bold mb-1">Final Reveal sudah terbuka</h2>
                    <p class="small mb-0">Lihat pola di balik semua order dan selesaikan dua tantangan singkat.</p>
                </div>
                <a class="btn btn-warning btn-lg rounded-pill fw-bold px-4" href="{{ route('tools.ko-ki.meeting-two.completed') }}">
                    {{ $factoryState['completed'] ? 'Lihat Badge' : 'Buka Final Reveal' }}
                </a>
            </section>
        @endif

        <div class="alert alert-info border-0 rounded-4 mt-3 mb-0">
            <strong>Target waktu:</strong> sekitar {{ $factory['estimated_minutes'] }} menit gameplay dan maksimal 55–60 menit untuk seluruh Pertemuan 2.
            Kelas 4 cukup mencapai target. Kelas 5 dapat mencoba bonus combo dan efisiensi—tanpa leaderboard.
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ko-ki/factory-rush.css') }}?v={{ filemtime(public_path('css/ko-ki/factory-rush.css')) }}">
@endpush
