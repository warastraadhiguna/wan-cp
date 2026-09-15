@extends('tools.ko-ki.layout')

@section('title', 'Bug Detective Agency')

@section('content')
    <div class="detective-shell mx-auto">
        <section class="koki-card detective-welcome overflow-hidden">
            <div class="row g-0 align-items-stretch">
                <div class="col-lg-7 p-4 p-md-5">
                    <p class="koki-eyebrow mb-2">Semester 1 · Unit 1 · Pertemuan 4</p>
                    <h1 class="display-4 koki-title mb-2">Bug Detective Agency</h1>
                    <div class="koki-dialogue my-4">Halo, Detektif! Ada beberapa program aneh di WAn Ko-Ki. Kita harus menemukan bug-nya!</div>
                    @if ($progress)
                        <div class="alert alert-success rounded-4">Progress <strong>{{ $progress->student_name }}</strong> · Kelas {{ $progress->grade }} masih tersimpan.</div>
                        <a href="{{ $resumeUrl }}" class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold">Lanjutkan Penyelidikan <i class="bi bi-arrow-right"></i></a>
                    @else
                        <form method="POST" action="{{ route('tools.ko-ki.meeting-four.start') }}" class="row g-3">
                            @csrf
                            <div class="col-md-7"><label for="student_name" class="form-label fw-bold">Nama panggilan</label><input id="student_name" name="student_name" class="form-control form-control-lg" maxlength="40" value="{{ old('student_name') }}" required autofocus placeholder="Contoh: Naya">@error('student_name')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                            <div class="col-md-5"><label for="grade" class="form-label fw-bold">Kelas</label><select id="grade" name="grade" class="form-select form-select-lg" required><option value="">Pilih kelas</option><option value="4" @selected(old('grade') == 4)>Kelas 4</option><option value="5" @selected(old('grade') == 5)>Kelas 5</option></select>@error('grade')<div class="text-danger small">{{ $message }}</div>@enderror</div>
                            <div class="col-12"><button class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold">Masuk Agensi Detektif <i class="bi bi-search"></i></button></div>
                        </form>
                    @endif
                </div>
                <div class="col-lg-5 detective-hero-art"><div class="detective-bot">🤖<span>🕵️</span></div><div class="detective-clues">🔎 <b>?</b> 🐞</div><strong>Cari · Perbaiki · Coba Lagi</strong></div>
            </div>
        </section>
    </div>
@endsection

@push('styles')<link rel="stylesheet" href="{{ asset('css/ko-ki/detective.css') }}?v={{ filemtime(public_path('css/ko-ki/detective.css')) }}">@endpush
