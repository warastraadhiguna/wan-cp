@if ($leaderboard->isEmpty())
    <p>Belum ada peserta yang menyelesaikan kuis.</p>
@else
    <ol class="list-decimal space-y-2 ps-6">
        @foreach ($leaderboard as $player)
            <li>
                <strong>{{ $player->nickname }}</strong>
                · {{ $player->correct_count }} benar
                · {{ number_format($player->correct_time_ms / 1000, 3, ',', '.') }} detik
            </li>
        @endforeach
    </ol>
@endif
