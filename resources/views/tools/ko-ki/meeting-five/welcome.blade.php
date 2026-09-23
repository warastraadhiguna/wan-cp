@extends('tools.ko-ki.layout')

@section('title', 'Ko-Ki Creator Lab')

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-9">
            <section class="koki-card p-4 p-md-5 text-center">
                <p class="koki-eyebrow">Semester 1 · Unit 1 · Pertemuan 5</p>
                <img class="koki-hero-logo koki-hero-logo-sm mb-4" src="{{ asset('wan-ko-ki.png') }}" alt="Logo WAn Ko-Ki">
                <h1 class="display-5 koki-title">Ko-Ki Creator Lab</h1>
                <p class="lead">Buat Gim Pertamamu</p>
                <p class="koki-muted">Halo! Pilih karakter, buat aturan dengan blok, lalu mainkan gim hasil karyamu.</p>
                @if ($progress)
                    <a href="{{ route('tools.ko-ki.meeting-five.lab') }}" class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold">Lanjutkan Karyaku</a>
                @else
                    <form method="POST" action="{{ route('tools.ko-ki.meeting-five.start') }}" class="row g-3 text-start mx-auto mt-3" style="max-width:32rem">
                        @csrf
                        <div class="col-12"><label class="form-label fw-bold" for="student-name">Nama panggilan</label><input class="form-control" id="student-name" name="student_name" required minlength="2" maxlength="40" value="{{ old('student_name') }}" autocomplete="nickname"></div>
                        <div class="col-12"><label class="form-label fw-bold" for="student-grade">Kelas</label><select class="form-select" id="student-grade" name="grade" required><option value="">Pilih kelas</option><option value="4" @selected(old('grade') == 4)>Kelas 4</option><option value="5" @selected(old('grade') == 5)>Kelas 5</option></select></div>
                        @if ($errors->any())<div class="col-12"><div class="alert alert-danger">{{ $errors->first() }}</div></div>@endif
                        <div class="col-12"><button class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold w-100">Masuk Creator Lab</button></div>
                    </form>
                @endif
            </section>
        </div>
    </div>
@endsection
