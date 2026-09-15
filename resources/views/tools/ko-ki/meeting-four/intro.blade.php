@extends('tools.ko-ki.layout')

@section('title', 'Misteri Pertama')

@section('content')
    <div class="detective-shell mx-auto">
        <section class="koki-card p-4 p-md-5">
            <p class="koki-eyebrow">Berkas Orientasi Detektif</p>
            <h1 class="display-5 koki-title">Halo, Detektif {{ $progress->student_name }}!</h1>
            <p class="lead koki-muted">Robot seharusnya menuju bendera, tetapi malah berbelok dan menabrak pohon.</p>
            <div class="detective-intro-scene my-4"><span class="detective-intro-robot">🤖</span><span class="detective-intro-path">······ ➜</span><span class="detective-intro-tree">🌳 💥</span><span class="detective-intro-goal">🚩</span></div>
            <form method="POST" action="{{ route('tools.ko-ki.meeting-four.intro.begin') }}">
                @csrf
                <fieldset><legend class="h3 fw-bold mb-3">Menurutmu, apa yang terjadi?</legend><div class="row g-3">
                    @foreach (['robot_nakal'=>'Robotnya nakal','komputer_marah'=>'Komputernya marah','ada_bug'=>'Ada bug di program','pohon_salah'=>'Pohonnya salah'] as $value => $label)
                        <div class="col-md-6"><div class="koki-answer"><input id="answer-{{ $value }}" type="radio" name="answer" value="{{ $value }}" required><label for="answer-{{ $value }}">{{ $label }}</label></div></div>
                    @endforeach
                </div></fieldset>
                @error('answer')<div class="text-danger mt-2">{{ $message }}</div>@enderror
                <button class="btn btn-primary koki-btn-primary btn-lg rounded-pill px-5 fw-bold mt-4">Buka CASE #001 <i class="bi bi-folder2-open"></i></button>
            </form>
        </section>
    </div>
@endsection

@push('styles')<link rel="stylesheet" href="{{ asset('css/ko-ki/detective.css') }}?v={{ filemtime(public_path('css/ko-ki/detective.css')) }}">@endpush
