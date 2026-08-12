@extends('tools.ko-ki.layout')

@section('title', 'Factory Rush')

@section('content')
    <div class="factory-welcome">
        <section class="koki-card overflow-hidden">
            <div class="row g-0 align-items-stretch">
                <div class="col-lg-7 p-4 p-md-5">
                    <p class="koki-eyebrow mb-2">WAn Ko-Ki · Pertemuan 2</p>
                    <h1 class="display-4 koki-title mb-2">Factory Rush</h1>
                    <p class="lead koki-muted mb-4">Kendalikan Ko-Ki Bot dan selesaikan order jus di pabrik mini.</p>

                    @if ($progress)
                        <div class="alert alert-light border rounded-4 mb-4">
                            <strong>Progress ditemukan.</strong><br>
                            {{ $progress->student_name }}, kelas {{ $progress->grade }}, dapat kembali ke pabrik.
                            <div class="mt-3">
                                <a class="btn btn-outline-primary rounded-pill px-4" href="{{ $resumeUrl }}">Lanjutkan Factory Rush</a>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tools.ko-ki.meeting-two.start') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="student_name">Nama panggilan</label>
                            <input class="form-control @error('student_name') is-invalid @enderror" id="student_name" name="student_name" type="text" value="{{ old('student_name') }}" maxlength="40" autocomplete="nickname" placeholder="Contoh: Naya" required autofocus>
                            @error('student_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="grade">Kelas</label>
                            <select class="form-select @error('grade') is-invalid @enderror" id="grade" name="grade" required>
                                <option value="">Pilih kelas</option>
                                @foreach ([4, 5] as $grade)
                                    <option value="{{ $grade }}" @selected((int) old('grade') === $grade)>Kelas {{ $grade }}</option>
                                @endforeach
                            </select>
                            @error('grade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold" type="submit">
                            Masuk Pabrik <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
                <div class="col-lg-5 factory-welcome-art p-4 p-md-5 d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <img class="koki-hero-logo koki-hero-logo-sm mb-4" src="{{ asset('wan-ko-ki.png') }}" alt="WAn Ko-Ki">
                        <div class="factory-welcome-icons" aria-hidden="true">🍎 + 💧 <span>⚙️</span> 🧃</div>
                        <p class="h4 fw-bold mt-4 mb-2">Ambil · Racik · Antar</p>
                        <p class="mb-0">Main sendiri, tanpa leaderboard.</p>
                        <p class="small mt-2 mb-0">Maksimal 55–60 menit</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ko-ki/factory-rush.css') }}?v={{ filemtime(public_path('css/ko-ki/factory-rush.css')) }}">
@endpush
