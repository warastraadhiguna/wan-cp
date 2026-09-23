@extends('tools.ko-ki.layout')

@section('title', 'Creator Lab — Buat Gim Pertamamu')

@section('content')
    <div class="creator-shell" data-creator-lab data-progress-id="{{ $progress->getKey() }}" data-state='@json($creatorState)' data-adventure-state='@json($creatorAdventure)' data-approved-steps='@json($creatorApprovals)' data-playtest-approved="{{ $playtestApproved ? '1' : '0' }}" data-save-url="{{ route('tools.ko-ki.meeting-five.progress') }}" data-playtest-status-url="{{ route('tools.ko-ki.meeting-five.playtest.status') }}" data-complete-url="{{ route('tools.ko-ki.meeting-five.adventure.complete') }}'>
        <header class="creator-header">
            <div><p class="koki-eyebrow mb-1">Semester 1 · Unit 1 · Pertemuan 5</p><h1>Ko-Ki Creator Lab</h1><p>Halo, {{ $progress->student_name }}! Buat gim, lalu coba sendiri.</p></div>
            <div class="creator-header-actions"><span class="creator-badge" data-badge hidden>🏅 Game Creator</span><div class="creator-save" data-save-status>✓ Tersimpan di perangkat</div></div>
        </header>

        <div class="creator-layout">
            <section class="creator-editor koki-card" aria-label="Editor visual gim">
                <div class="creator-editor-heading"><div><span class="creator-kicker" data-stage-count>TANTANGAN 1 DARI 4</span><h2 data-stage-title>Control</h2></div><span class="creator-progress" data-progress-label>0 / 4 selesai</span></div>
                <div class="creator-stage-tabs" data-stage-tabs></div>
                <div class="creator-editor-body" data-editor-body></div>
                <div class="creator-feedback" data-editor-feedback role="status" aria-live="polite">Pilih blok untuk mulai membuat gim.</div>
            </section>

            <section class="creator-preview koki-card" aria-label="Pratinjau gim">
                <div class="creator-mode-bar" data-mode-bar hidden><strong data-mode-label>MAIN GAME-KU</strong><div><button type="button" data-finish-friend hidden>✓ Selesai Main</button><button type="button" data-exit-mode>✕ Kembali ke Creator Lab</button></div></div>
                <div class="creator-preview-heading"><div><span class="creator-kicker">GIM BUATANMU</span><h2 data-game-name>Green Adventure</h2></div><div class="creator-preview-stats"><span class="creator-timer" data-timer hidden>⏱ 30 detik</span><span class="creator-score">SKOR <b data-score>0 / 3</b></span></div></div>
                <div class="creator-arena" data-arena tabindex="0" aria-label="Arena permainan. Gunakan tombol kiri, kanan, dan spasi.">
                    <div class="creator-sky" data-sky></div>
                    <div class="creator-ground"></div>
                    <div class="creator-start">START</div>
                    <div class="creator-avatar" data-avatar>🤖</div>
                    <div class="creator-item" data-item>🌱</div>
                    <div class="creator-item creator-second-item" data-second-item hidden>⭐</div>
                    <div class="creator-obstacle" data-obstacle>🪨</div>
                    <div class="creator-obstacle creator-second-obstacle" data-second-obstacle hidden>🪨</div>
                    <div class="creator-obstacle creator-dragon-obstacle" data-third-obstacle hidden>🔥</div>
                    <div class="creator-obstacle creator-dragon-obstacle" data-fourth-obstacle hidden>🪨</div>
                    <div class="creator-dragon" data-dragon hidden><span>🐉</span><i>🔥</i></div>
                    <div class="creator-collect-pop" data-collect-pop hidden>+1</div>
                    <div class="creator-win" data-win hidden>🏆 MENANG!</div>
                </div>
                <div class="creator-friend-feedback" data-friend-feedback role="dialog" aria-modal="true" aria-labelledby="creator-friend-feedback-title" hidden>
                    <h3 id="creator-friend-feedback-title">Bagaimana gim temanmu?</h3>
                    <p>Pilih satu kesan. Jawaban ini hanya tersimpan di perangkat.</p>
                    <div class="creator-feedback-options" data-feedback-options>
                        <button type="button" data-friend-choice="Seru">🎉 Seru</button>
                        <button type="button" data-friend-choice="Lucu">😄 Lucu</button>
                        <button type="button" data-friend-choice="Menantang">🧩 Menantang</button>
                        <button type="button" data-friend-choice="Keren">✨ Keren</button>
                    </div>
                    <div data-feedback-thanks hidden><strong>Terima kasih sudah mencoba!</strong><button type="button" data-friend-replay>▶ Main Lagi</button></div>
                </div>
                <div class="creator-level-result" data-level-result role="dialog" aria-modal="true" aria-labelledby="creator-level-result-title" hidden>
                    <div class="creator-level-result-icon">⭐</div>
                    <h3 id="creator-level-result-title">LEVEL SELESAI!</h3>
                    <p data-level-result-text>Gim buatanmu berhasil melewati tantangan.</p>
                    <div><button type="button" data-level-replay>↺ Main Lagi</button><button type="button" data-level-next>Level Berikutnya →</button><button type="button" data-level-finale hidden>Lanjut ke Penutup →</button></div>
                </div>
                <div class="creator-finale" data-creator-finale role="dialog" aria-modal="true" hidden>
                    <section data-finale-evaluation>
                        <p class="creator-kicker">PENUTUP PERTEMUAN 5</p><div class="creator-finale-icon">🧩</div>
                        <h3>Evaluasi Game Creator</h3><p>Pilih jawaban yang paling tepat. Kamu boleh mencoba lagi.</p>
                        <fieldset><legend>1. Apa yang dilakukan seorang pembuat game?</legend>
                            <label><input type="radio" name="creator_answer" value="mengatur_game"> Mengatur karakter, aturan, rintangan, dan tujuan</label>
                            <label><input type="radio" name="creator_answer" value="menunggu_game"> Menunggu komputer membuat semuanya sendiri</label>
                        </fieldset>
                        <fieldset><legend>2. Jika game belum bekerja seperti rencana, apa yang dilakukan?</legend>
                            <label><input type="radio" name="debug_answer" value="uji_perbaiki"> Menguji, memperbaiki rule, lalu mencoba lagi</label>
                            <label><input type="radio" name="debug_answer" value="menyerah"> Langsung berhenti dan menghapus game</label>
                        </fieldset>
                        <p class="creator-finale-feedback" data-finale-feedback aria-live="polite"></p>
                        <button type="button" data-check-finale>Periksa Jawabanku</button>
                    </section>
                    <section class="creator-medal" data-finale-medal hidden>
                        <p class="creator-kicker">PERTEMUAN 5 SELESAI</p><div class="creator-medal-icon">🏅</div>
                        <h3>Ko-Ki Game Creator</h3><p>Medali kelulusan ini diberikan kepada</p>
                        <strong>{{ $progress->student_name }}</strong><span>Kelas {{ $progress->grade }}</span>
                        <div class="creator-medal-message">Kamu berhasil membuat game, mengujinya, memperbaikinya, dan menamatkan 10 level Creator Adventure.</div>
                        <a href="{{ route('tools.ko-ki.index') }}">Kembali ke Daftar Materi</a>
                    </section>
                </div>
                <p class="creator-game-message" data-game-message role="status">Tekan TEST GAME, lalu coba tombolnya.</p>
                <div class="creator-play-controls">
                    <button type="button" data-play-key="LEFT" aria-label="Kiri">← Kiri</button>
                    <button type="button" data-play-key="SPACE" aria-label="Lompat">↑ Lompat</button>
                    <button type="button" data-play-key="RIGHT" aria-label="Kanan">Kanan →</button>
                </div>
                <div class="creator-play-actions"><button type="button" class="creator-test" data-test-game>▶ TEST GAME</button><button type="button" class="creator-reset" data-reset-game>↺ Ulangi Gim</button></div>
                <div class="creator-mode-launch" data-mode-launch hidden><button type="button" data-open-mode="self">🎮 MAIN GAME-KU</button><button type="button" data-open-mode="friend">🤝 MAIN BERGANTIAN</button></div>
                <p class="creator-key-help">Keyboard: ← / → / Space. Tombol di layar juga bisa dipakai.</p>
            </section>
        </div>
    </div>
@endsection

@push('styles')<link rel="stylesheet" href="{{ asset('css/ko-ki/creator-lab.css') }}?v={{ filemtime(public_path('css/ko-ki/creator-lab.css')) }}-finale-13">@endpush
@push('scripts')<script type="module" src="{{ asset('js/ko-ki/creator-lab.js') }}?v={{ filemtime(public_path('js/ko-ki/creator-lab.js')) }}-finale-13"></script>@endpush
