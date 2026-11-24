@extends('tools.quiz.layout')

@section('title', 'Siap main?')

@section('content')
    <section class="quiz-card quiz-lobby">
        <div class="quiz-art" aria-hidden="true">
            <span>▲</span><span>◆</span><span>●</span><span>■</span>
        </div>
        @if ($bank)
            <h1>{{ $meeting->title }}</h1>
            <p>{{ count($bank->questions) }} soal · {{ $bank->seconds_per_question }} detik per soal</p>
            @if ($attempt)
                <a class="quiz-button" href="{{ route($attempt->completed_at ? 'tools.quiz.results' : 'tools.quiz.play', $attempt) }}">
                    {{ $attempt->completed_at ? 'Lihat peringkat' : 'Lanjut main' }} →
                </a>
            @else
                <form class="quiz-join" method="POST" action="{{ route('tools.quiz.join') }}">
                    @csrf
                    <input type="hidden" name="bank" value="{{ $bank->id }}">
                    <label for="nickname">Nama panggilan</label>
                    <input id="nickname" type="text" name="nickname" value="{{ old('nickname') }}" maxlength="30"
                        placeholder="Contoh: Kapten Naya" autocomplete="off" required @error('nickname') aria-describedby="nickname-error" @enderror>
                    @error('nickname') <p class="quiz-error" id="nickname-error" role="alert">{{ $message }}</p> @enderror
                    <button class="quiz-button" type="submit">Gabung kuis →</button>
                </form>
            @endif
        @else
            <h1>Siap untuk kuis?</h1>
            <p>Tunggu pengajar membuka pertemuan, lalu cek lagi.</p>
            <a class="quiz-button" href="{{ route('tools.quiz.index') }}">Cek pertemuan ↻</a>
        @endif
    </section>
@endsection
