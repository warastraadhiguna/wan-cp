@extends('tools.ko-ki.layout')

@section('title', 'Daftar Materi')

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <header class="row g-4 align-items-center mb-4">
                <div class="col-md-8">
                    <p class="koki-eyebrow mb-2">WAn Ko-Ki · Koding for Kids</p>
                    <h1 class="display-5 koki-title mb-3">Belajar Koding, Satu Pertemuan Sekaligus</h1>
                    <p class="lead koki-muted mb-0">
                        Pilih materi, ikuti aktivitasnya, dan belajar berpikir seperti programer bersama Ko-Ki Bot.
                    </p>
                </div>
                <div class="col-md-4 text-center">
                    <img
                        class="koki-hero-logo koki-hero-logo-sm"
                        src="{{ asset('wan-ko-ki.png') }}"
                        alt="WAn Ko-Ki, Koding for Kids"
                    >
                </div>
            </header>

            <section class="koki-card p-4 p-md-5">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                    <div>
                        <span class="badge rounded-pill text-bg-primary px-3 py-2 mb-3">Semester 1</span>
                        <h2 class="h2 fw-bold mb-2">Belajar Berpikir seperti Programer</h2>
                        <p class="koki-muted mb-0">Mulai dari mengenal peran manusia, komputer, dan program.</p>
                    </div>
                    <div class="text-md-end">
                        <span class="badge rounded-pill text-bg-light border px-3 py-2">Kelas 1–6</span>
                    </div>
                </div>

                <div class="koki-curriculum-line">
                    <p class="koki-eyebrow mb-2">Unit 1</p>
                    <h3 class="h4 fw-bold mb-3">Aku, Komputer dan Koding</h3>

                    <article class="koki-step p-3 p-md-4">
                        <div class="row g-3 align-items-center">
                            <div class="col-auto">
                                <span class="koki-step-number">1</span>
                            </div>
                            <div class="col">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge text-bg-success">Tersedia</span>
                                    <span class="badge text-bg-light border">Maksimal 60 menit</span>
                                </div>
                                <p class="small fw-bold text-primary mb-1">Pertemuan 1</p>
                                <h4 class="h3 fw-bold mb-2">Apakah Komputer Itu Pintar?</h4>
                                <p class="koki-muted mb-0">Ko-Ki Bot: Komputer Paling Patuh</p>
                            </div>
                            <div class="col-12 col-lg-auto">
                                @if ($progress)
                                    <a class="btn btn-primary koki-btn-primary rounded-pill px-4 py-2 fw-bold w-100" href="{{ $resumeUrl }}">
                                        {{ $progress->completed_at ? 'Lihat Hasil' : 'Lanjutkan Pertemuan 1' }}
                                        <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                                    </a>
                                @else
                                    <a class="btn btn-primary koki-btn-primary rounded-pill px-4 py-2 fw-bold w-100" href="{{ route('tools.ko-ki.welcome') }}">
                                        Buka Pertemuan 1
                                        <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </article>

                    <article class="koki-step p-3 p-md-4 mt-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-auto">
                                <span class="koki-step-number">2</span>
                            </div>
                            <div class="col">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge text-bg-success">Tersedia</span>
                                    <span class="badge text-bg-light border">Kelas 4–5</span>
                                    <span class="badge text-bg-light border">Maksimal 60 menit</span>
                                </div>
                                <p class="small fw-bold text-primary mb-1">Pertemuan 2</p>
                                <h4 class="h3 fw-bold mb-2">Bagaimana Komputer Bekerja?</h4>
                                <p class="koki-muted mb-0">Factory Rush: racik dan antar order bersama Ko-Ki Bot</p>
                            </div>
                            <div class="col-12 col-lg-auto">
                                @if ($meetingTwoProgress)
                                    <a class="btn btn-primary koki-btn-primary rounded-pill px-4 py-2 fw-bold w-100" href="{{ $meetingTwoResumeUrl }}">
                                        {{ $meetingTwoCompleted ? 'Mainkan Lagi' : 'Lanjutkan Pertemuan 2' }}
                                        <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                                    </a>
                                @else
                                    <a class="btn btn-primary koki-btn-primary rounded-pill px-4 py-2 fw-bold w-100" href="{{ route('tools.ko-ki.meeting-two.welcome') }}">
                                        Buka Pertemuan 2
                                        <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </article>

                    <article class="koki-step p-3 p-md-4 mt-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-auto"><span class="koki-step-number">3</span></div>
                            <div class="col">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <span class="badge text-bg-success">Tersedia</span>
                                    <span class="badge text-bg-light border">Kelas 4–5</span>
                                    <span class="badge text-bg-light border">45–50 menit</span>
                                </div>
                                <p class="small fw-bold text-primary mb-1">Pertemuan 3</p>
                                <h4 class="h3 fw-bold mb-2">Ko-Ki Rescue Mission</h4>
                                <p class="koki-muted mb-0">Susun instruksi yang jelas, tepat, lengkap, dan berurutan.</p>
                            </div>
                            <div class="col-12 col-lg-auto">
                                @if ($meetingThreeProgress)
                                    <a class="btn btn-primary koki-btn-primary rounded-pill px-4 py-2 fw-bold w-100" href="{{ $meetingThreeResumeUrl }}">
                                        {{ $meetingThreeCompleted ? 'Lihat Medali' : 'Lanjutkan Pertemuan 3' }} <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                                    </a>
                                @else
                                    <a class="btn btn-primary koki-btn-primary rounded-pill px-4 py-2 fw-bold w-100" href="{{ route('tools.ko-ki.meeting-three.welcome') }}">
                                        Buka Pertemuan 3 <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </div>
@endsection
