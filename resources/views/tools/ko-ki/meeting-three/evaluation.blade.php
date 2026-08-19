@extends('tools.ko-ki.layout')

@section('title', $rescueState['evaluation_passed'] ? 'Medali Rescue Mission' : 'Evaluasi Rescue Mission')

@section('content')
    <div class="rescue-welcome mx-auto">
        @if ($rescueState['evaluation_passed'])
            <section class="koki-card p-4 p-md-5 text-center">
                <p class="koki-eyebrow">Pertemuan 3 Selesai</p>
                <div class="display-1 mb-2">🏅</div>
                <h1 class="display-4 koki-title">Komandan Rescue Ko-Ki</h1>
                <p class="lead">Medali ini diberikan kepada <strong>{{ $progress->student_name }}</strong> · Kelas {{ $progress->grade }}</p>
                <div class="koki-dialogue mx-auto my-4">Kamu berhasil menyusun instruksi yang jelas, tepat, lengkap, dan berurutan. Total bintangmu: <strong>⭐ {{ $rescueState['total_stars'] }} / 18</strong></div>
                <a href="{{ route('tools.ko-ki.index') }}" class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold">Kembali ke Daftar Materi</a>
            </section>
        @else
            <section class="koki-card p-4 p-md-5">
                <p class="koki-eyebrow mb-2">Evaluasi Akhir · 3 Tantangan</p>
                <h1 class="display-5 koki-title mb-2">Buktikan kamu siap menjadi Komandan Rescue!</h1>
                <p class="koki-muted mb-4">Pilih jawaban yang paling tepat. Kamu boleh mencoba lagi.</p>
                @if (session('error')) <div class="alert alert-warning rounded-4 fw-bold">{{ session('error') }}</div> @endif
                <form method="POST" action="{{ route('tools.ko-ki.meeting-three.evaluation.submit') }}">
                    @csrf
                    @php
                        $questions = [
                            ['clear_instruction','1. Mengapa instruksi untuk Ko-Ki harus jelas?',['ikuti_persis'=>'Ko-Ki mengikuti instruksi persis seperti yang diberikan','menebak'=>'Ko-Ki dapat menebak langkah yang hilang','memilih_sendiri'=>'Ko-Ki akan memilih jalannya sendiri']],
                            ['missing_command','2. Ko-Ki sudah di dekat tanaman tetapi tidak membawa air. Command apa yang kurang?',['ambil_air'=>'AMBIL AIR','putar_kiri'=>'PUTAR KIRI','maju'=>'MAJU']],
                            ['sequence','3. Pilih urutan yang masuk akal untuk menyiram tanaman.',['ambil-maju-siram'=>'AMBIL AIR → MAJU → SIRAM','siram-maju-ambil'=>'SIRAM → MAJU → AMBIL AIR','maju-siram-ambil'=>'MAJU → SIRAM → AMBIL AIR']],
                        ];
                    @endphp
                    @foreach ($questions as [$name,$question,$choices])
                        <fieldset class="rescue-panel mb-3"><legend class="h5 fw-bold">{{ $question }}</legend><div class="row g-2">
                            @foreach ($choices as $value => $label)
                                <div class="col-12 col-md-{{ count($choices) === 3 ? '4' : '6' }}"><label class="d-flex align-items-center gap-2 border rounded-4 p-3 h-100 fw-bold"><input class="form-check-input mt-0" type="radio" name="{{ $name }}" value="{{ $value }}" @checked(old($name) === $value) required> {{ $label }}</label></div>
                            @endforeach
                        </div>@error($name)<div class="text-danger small mt-2">{{ $message }}</div>@enderror</fieldset>
                    @endforeach
                    <button class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold">Periksa Jawabanku</button>
                </form>
            </section>
        @endif
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/ko-ki/rescue-mission.css') }}?v={{ filemtime(public_path('css/ko-ki/rescue-mission.css')) }}">
@endpush
