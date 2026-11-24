@extends('tools.quiz.layout')

@section('title', 'Peringkat')

@section('content')
    <section class="quiz-card quiz-finish">
        <div class="quiz-emblem quiz-emblem-warm" aria-hidden="true">🏆</div>
        <h1>Kuis selesai!</h1>
        <p>{{ $meeting->title }} · {{ $attempt->nickname }}</p>
        <dl class="quiz-stats">
            <div><dt>Peringkatmu</dt><dd>#{{ $rank }}</dd></div>
            <div><dt>Jawaban benar</dt><dd>{{ $attempt->correct_count }}/{{ count($bank->questions) }}</dd></div>
            <div><dt>Waktu jawaban benar</dt><dd>{{ number_format($attempt->correct_time_ms / 1000, 3, ',', '.') }} dtk</dd></div>
        </dl>
    </section>

    <section class="quiz-card quiz-board">
        <div class="quiz-board-head">
            <h2>Papan peringkat</h2>
            <a href="{{ route('tools.quiz.results', $attempt) }}">Perbarui ↻</a>
        </div>
        <p>Jawaban benar terbanyak di atas. Jika seri, total waktu jawaban benar tercepat menang.</p>
        <ol class="quiz-ranks">
            @foreach ($leaderboard as $player)
                <li @class(['quiz-rank', 'quiz-rank-top' => $loop->first, 'quiz-rank-you' => $player->id === $attempt->id])>
                    <span class="quiz-rank-number" aria-label="Peringkat {{ $loop->iteration }}">{{ $loop->iteration }}</span>
                    <strong>{{ $player->nickname }} @if ($player->id === $attempt->id) <span>(kamu)</span> @endif</strong>
                    <span>{{ $player->correct_count }} benar · {{ number_format($player->correct_time_ms / 1000, 3, ',', '.') }} dtk</span>
                </li>
            @endforeach
        </ol>
    </section>
@endsection
