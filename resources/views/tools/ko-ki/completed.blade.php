@extends('tools.ko-ki.layout')

@section('title', 'Pertemuan Selesai')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <section class="koki-card p-4 p-md-5 text-center">
                <img
                    class="koki-hero-logo koki-hero-logo-sm mb-4"
                    src="{{ asset('wan-ko-ki.png') }}"
                    alt="WAn Ko-Ki, Koding for Kids"
                >
                <div class="display-2 mb-3" aria-hidden="true">🏅</div>
                <p class="koki-eyebrow mb-2">Pertemuan 1 selesai</p>
                <div class="badge rounded-pill text-bg-warning text-dark fs-6 px-4 py-2 mb-3">
                    Pemberi Instruksi Pemula
                </div>
                <h1 class="display-5 koki-title mb-3">Hebat, {{ $progress->student_name }}!</h1>
                <p class="lead mb-1">Hari ini kamu sudah membuat program pertamamu.</p>
                <p class="lead koki-muted mb-5">Kamu berpikir, Ko-Ki Bot menjalankan!</p>

                <div class="row g-3 text-start mb-4">
                    @foreach ([
                        ['icon' => 'bi-check-circle-fill', 'label' => 'Misi selesai', 'value' => count($progress->completed_levels ?? []).' dari 4'],
                        ['icon' => 'bi-play-circle-fill', 'label' => 'Program dijalankan', 'value' => $progress->attempt_count.' kali'],
                        ['icon' => 'bi-bug-fill', 'label' => 'Bug diperbaiki', 'value' => ($progress->level_states[3]['corrected_bug'] ?? false) ? 'Sudah' : 'Belum tercatat'],
                        ['icon' => 'bi-chat-square-text-fill', 'label' => 'Refleksi dikirim', 'value' => filled($progress->reflection) ? 'Sudah' : 'Belum'],
                    ] as $summary)
                        <div class="col-sm-6">
                            <div class="koki-step d-flex align-items-center gap-3">
                                <i class="bi {{ $summary['icon'] }} fs-2 text-success" aria-hidden="true"></i>
                                <div>
                                    <h2 class="h6 fw-bold mb-1">{{ $summary['label'] }}</h2>
                                    <p class="mb-0 koki-muted">{{ $summary['value'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="koki-dialogue text-start mb-4">
                    <strong>Refleksimu:</strong><br>
                    {{ $progress->reflection }}
                </div>

                <a class="btn btn-outline-primary rounded-pill px-4" href="{{ route('tools.ko-ki.welcome') }}">
                    Kembali ke halaman pembuka
                </a>
            </section>
        </div>
    </div>
@endsection
