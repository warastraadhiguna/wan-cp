@extends('tools.ko-ki.layout')

@section('title', 'Ko-Ki Rescue Mission')

@section('content')
    <div class="rescue-welcome mx-auto">
        <section class="koki-card overflow-hidden">
            <div class="row g-0 align-items-stretch">
                <div class="col-lg-7 p-4 p-md-5">
                    <p class="koki-eyebrow mb-2">WAn Ko-Ki · Pertemuan 3</p>
                    <h1 class="display-4 koki-title mb-3">Ko-Ki Rescue Mission</h1>
                    <div class="koki-dialogue mb-4">
                        Halo! Taman kota membutuhkan bantuanmu. Susun program agar Ko-Ki mengambil air dan menyelamatkan tanaman.
                    </div>
                    <div class="rescue-intro-flow mb-4" aria-label="Cara bermain">
                        <span><b>1</b> Susun command</span><i>→</i>
                        <span><b>2</b> Prediksi</span><i>→</i>
                        <span><b>3</b> RUN</span><i>→</i>
                        <span><b>4</b> Perbaiki</span>
                    </div>
                    @if ($progress)
                        <div class="alert alert-success rounded-4 mb-3">Halo, <strong>{{ $progress->student_name }}</strong> dari Kelas {{ $progress->grade }}. Progress-mu masih tersimpan.</div>
                        <a class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold" href="{{ $resumeUrl }}">Lanjutkan Mission <i class="bi bi-arrow-right ms-1"></i></a>
                    @else
                        <form method="POST" action="{{ route('tools.ko-ki.meeting-three.start') }}" class="row g-3">
                            @csrf
                            <div class="col-md-7">
                                <label for="student_name" class="form-label fw-bold">Nama panggilan</label>
                                <input id="student_name" name="student_name" class="form-control form-control-lg rounded-4" maxlength="40" value="{{ old('student_name') }}" required autofocus placeholder="Contoh: Naya">
                                @error('student_name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label for="grade" class="form-label fw-bold">Kelas</label>
                                <select id="grade" name="grade" class="form-select form-select-lg rounded-4" required>
                                    <option value="">Pilih kelas</option>
                                    <option value="4" @selected(old('grade') == 4)>Kelas 4</option>
                                    <option value="5" @selected(old('grade') == 5)>Kelas 5</option>
                                </select>
                                @error('grade') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12"><button class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold">Mulai Pertemuan 3 <i class="bi bi-arrow-right ms-1"></i></button></div>
                        </form>
                    @endif
                </div>
                <div class="col-lg-5 rescue-welcome-art">
                    <span class="rescue-big-bot">🤖</span><span>💧</span><span>🌱</span><span>🌼</span>
                    <strong>Instruksi yang tepat<br>menyelamatkan taman!</strong>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ko-ki/rescue-mission.css') }}?v={{ filemtime(public_path('css/ko-ki/rescue-mission.css')) }}">
@endpush
