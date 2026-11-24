@extends('tools.quiz.layout')

@section('title', $meeting->title)

@section('content')
    <div class="quiz-meta">
        <span>{{ $meeting->title }}</span>
        <strong>{{ $attempt->nickname }}</strong>
    </div>

    @if ($question)
        <section class="quiz-card quiz-question">
            <div class="quiz-question-bar">
                <span>Soal {{ $position + 1 }} / {{ $total }}</span>
                <span class="quiz-timer" data-timer role="timer" aria-label="Sisa waktu">
                    {{ (int) ceil($remaining / 1000) }} dtk
                </span>
            </div>
            <progress class="quiz-clock" data-clock aria-label="Sisa waktu"
                value="{{ $remaining }}" max="{{ $bank->seconds_per_question * 1000 }}"></progress>
            <h1>{{ $question['prompt'] }}</h1>
        </section>

        <form class="quiz-options" method="POST" action="{{ route('tools.quiz.answer', $attempt) }}"
            data-answer-form data-remaining="{{ $remaining }}">
            @csrf
            <input type="hidden" name="position" value="{{ $position }}">
            @foreach (['A' => '▲', 'B' => '◆', 'C' => '●', 'D' => '■'] as $letter => $shape)
                <button class="quiz-option quiz-option-{{ strtolower($letter) }}" type="submit" name="choice" value="{{ $letter }}">
                    <span class="quiz-option-key">
                        <span aria-hidden="true">{{ $shape }}</span>
                        {{ $letter }}
                    </span>
                    <span>{{ $question['options'][$letter] }}</span>
                </button>
            @endforeach
        </form>
        <noscript>
            <p class="quiz-note">Aktifkan JavaScript untuk melihat timer bergerak. Jawaban tetap mengikuti batas waktu di server.</p>
        </noscript>
        @push('scripts')
            <script src="{{ asset('js/quiz.js') }}?v={{ filemtime(public_path('js/quiz.js')) }}" defer></script>
        @endpush
    @else
        <section class="quiz-card quiz-feedback">
            @if ($previous)
                @if ($previous['correct'])
                    <div class="quiz-emblem quiz-emblem-success" aria-hidden="true">✓</div>
                    <h1>Jawabanmu tepat!</h1>
                    <p>Kamu menjawab dalam {{ number_format($previous['elapsed_ms'] / 1000, 2, ',', '.') }} detik.</p>
                @else
                    <div class="quiz-emblem quiz-emblem-warm" aria-hidden="true">{{ $previous['choice'] ? '↗' : '◷' }}</div>
                    <h1>{{ $previous['choice'] ? 'Terus semangat!' : 'Waktunya habis!' }}</h1>
                    <p>Jawaban yang benar adalah <strong>{{ $previousCorrectOption }}</strong>.</p>
                @endif
            @else
                <div class="quiz-emblem" aria-hidden="true">⚡</div>
                <h1>Ayo mulai, {{ $attempt->nickname }}!</h1>
                <p>Pilih satu jawaban sebelum timer habis. Timer mulai saat kamu membuka soal.</p>
            @endif
            <form method="POST" action="{{ route('tools.quiz.next', $attempt) }}">
                @csrf
                <input type="hidden" name="position" value="{{ $position }}">
                <button class="quiz-button" type="submit">{{ $position ? 'Soal berikutnya' : 'Mulai kuis' }} →</button>
            </form>
        </section>
    @endif
@endsection
